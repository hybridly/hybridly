import type { App, DefineComponent, Plugin as VuePlugin } from 'vue'
import { createApp, h } from 'vue'
import type { Plugin, RouterContext, RouterContextOptions, RoutingConfiguration } from '@hybridly/core'
import { createRouter } from '@hybridly/core'
import { showPageComponentErrorModal, debug, random } from '@hybridly/utils'
import type { Axios } from 'axios'
import type { ProgressOptions } from '@hybridly/progress-plugin'
import { progress } from '@hybridly/progress-plugin'
import { wrapper } from './components/wrapper'
import { state } from './stores/state'
import { devtools } from './devtools'
import { dialogStore } from './stores/dialog'
import { onMountedCallbacks } from './stores/mount'

/**
 * Initializes Hybridly's router and context.
 */
export async function initializeHybridly(options: InitializeOptions = {}) {
	const resolved = options as ResolvedInitializeOptions
	const { element, payload, resolve } = prepare(resolved)

	if (!element) {
		throw new Error('Could not find an HTML element to initialize Vue on.')
	}

	state.setContext(await createRouter({
		axios: resolved.axios,
		plugins: resolved.plugins,
		serializer: resolved.serializer,
		responseErrorModals: resolved.responseErrorModals ?? process.env.NODE_ENV === 'development',
		adapter: {
			resolveComponent: resolve,
			onWaitingForMount: (callback) => {
				onMountedCallbacks.push(callback)
			},
			onDialogClose: async() => {
				dialogStore.hide()
			},
			onContextUpdate: (context) => {
				state.setContext(context)
			},
			onViewSwap: async(options) => {
				state.setView(options.component)
				state.setProperties(options.properties)

				if (!options.preserveState && !options.dialog) {
					state.setViewKey(random())
				}

				if (options.dialog) {
					dialogStore.setComponent(await resolve(options.dialog.component) as any)
					dialogStore.setProperties(options.dialog.properties)
					dialogStore.setKey(options.dialog.key)
					dialogStore.show()
				} else {
					dialogStore.hide()
				}
			},
		},
		payload,
	}))

	// Using `window` is the only way I found to be able to get the route collection,
	// since this initialization is ran after the Vite plugin is done executing.
	if (typeof window !== 'undefined') {
		window.addEventListener<any>('hybridly:routing', (event: CustomEvent<RoutingConfiguration>) => {
			state.context.value?.adapter.updateRoutingConfiguration(event.detail)
		})

		window.dispatchEvent(new CustomEvent('hybridly:routing', { detail: window?.hybridly?.routing }))
	}

	const render = () => h(wrapper as any)

	if (options.setup) {
		return await options.setup({
			element,
			wrapper,
			render,
			hybridly: devtools,
			props: { context: state.context.value! },
		})
	}

	const app = createApp({ render })

	if (resolved.devtools !== false) {
		app.use(devtools)
	}

	await options.enhanceVue?.(app)
	return app.mount(element)
}

function prepare(options: ResolvedInitializeOptions) {
	debug.adapter('vue', 'Preparing Hybridly with options:', options)
	const isServer = typeof window === 'undefined'
	const id = options.id ?? 'root'
	const element = document?.getElementById(id) ?? undefined

	debug.adapter('vue', `Element "${id}" is:`, element)
	const payload = element?.dataset.payload
		? JSON.parse(element!.dataset.payload!)
		: undefined

	if (!payload) {
		throw new Error('No payload found. Are you using the `@hybridly` directive?')
	}

	if (options.cleanup !== false) {
		delete element!.dataset.payload
	}

	debug.adapter('vue', 'Resolved:', { isServer, element, payload })
	const resolve = async(name: string): Promise<DefineComponent> => {
		debug.adapter('vue', 'Resolving component', name)

		if (!options.components) {
			throw new Error('Either `initializeHybridly#resolve` or `initializeHybridly#pages` should be defined.')
		}

		return await resolveViewComponent(name, options)
	}

	if (options.progress !== false) {
		options.plugins = [
			progress(typeof options.progress === 'object' ? options.progress : {}),
			...options.plugins ?? [],
		]
	}

	return {
		isServer,
		element,
		payload,
		resolve,
	}
}

/**
 * Resolves a view by its name.
 */
async function resolveViewComponent(name: string, options: ResolvedInitializeOptions) {
	const components = options.components.imported!
	const result = options.components.views.find((view) => name === view.identifier)
	const path = Object.keys(components)
		.sort((a, b) => a.length - b.length)
		.find((path) => result ? path.endsWith(result?.path) : false)

	if (!result || !path) {
		console.warn(`Page component [${name}] not found. Available components: `, options.components.views.map(({ identifier }) => identifier))
		showPageComponentErrorModal(name)
		return
	}

	let component = typeof components[path] === 'function'
		? await components[path]()
		: components[path]

	component = component.default ?? component

	return component
}

type ResolvedInitializeOptions = InitializeOptions & {
	components: {
		/** List of views defined from the back-end. */
		views: Component[]
		/** List of layouts defined from the back-end. */
		layouts: Component[]
		/** List of components imported by `import.meta.glob`. */
		imported: Record<string, any>
	}
}

interface Component {
	path: string
	identifier: string
	namespace: string
}

interface InitializeOptions {
	/** Callback that gets executed before Vue is mounted. */
	enhanceVue?: (vue: App<Element>) => any
	/** ID of the app element. */
	id?: string
	/** Clean up the host element's payload dataset after loading. */
	cleanup?: boolean
	/** Whether to set up the devtools plugin. */
	devtools?: boolean
	/** Whether to display response error modals. */
	responseErrorModals?: boolean
	/** Custom history state serialization functions. */
	serializer?: RouterContextOptions['serializer']
	/** Progressbar options. */
	progress?: false | Partial<ProgressOptions>
	/** Sets up the hybridly router. */
	setup?: (options: SetupArguments) => any
	/** List of Hybridly plugins. */
	plugins?: Plugin[]
	/** Custom Axios instance. */
	axios?: Axios
}

interface SetupArguments {
	/** DOM element to mount Vue on. */
	element: Element
	/** Hybridly wrapper component. */
	wrapper: any
	/** Hybridly wrapper component properties. */
	props: {
		context: RouterContext
	}
	/** Vue plugin that registers the devtools. */
	hybridly: VuePlugin
	/** Renders the wrapper. */
	render: () => ReturnType<typeof h>
}
