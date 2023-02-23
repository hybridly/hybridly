import type { App, DefineComponent, Plugin as VuePlugin } from 'vue'
import { createApp, h } from 'vue'
import type { HybridPayload, Plugin, ResolveComponent, RouterContext, RouterContextOptions, RoutingConfiguration } from '@hybridly/core'
import { createRouter } from '@hybridly/core'
import { showPageComponentErrorModal, debug, random } from '@hybridly/utils'
import type { Axios } from 'axios'
import type { HybridlyConfig } from 'hybridly'
import type { ProgressOptions } from '@hybridly/progress-plugin'
import { progress } from '@hybridly/progress-plugin'
import { wrapper } from './components/wrapper'
import { state } from './stores/state'
import { devtools } from './devtools'
import { dialogStore } from './stores/dialog'

/**
 * Initializes Hybridly's router and context.
 */
export async function initializeHybridly(options: InitializeOptions = {}) {
	const resolved = options as ResolvedInitializeOptions
	const { element, payload, resolve } = prepare(resolved)

	if (!element) {
		throw new Error('Could not find an HTML element to initialize Vue on.')
	}

	if (!payload) {
		throw new Error('No payload. Are you using `@hybridly` or the `payload` option?')
	}

	state.setContext(await createRouter({
		axios: resolved.axios,
		plugins: resolved.plugins,
		serializer: resolved.serializer,
		responseErrorModals: options.responseErrorModals ?? process.env.NODE_ENV === 'development',
		adapter: {
			resolveComponent: resolve,
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
					dialogStore.setComponent(await resolve(options.dialog.component))
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
	const payload = options.payload ?? element?.dataset.payload
		? JSON.parse(element!.dataset.payload!)
		: undefined

	if (options.cleanup !== false) {
		delete element!.dataset.payload
	}

	debug.adapter('vue', 'Resolved:', { isServer, element, payload })
	const resolve = async(name: string): Promise<DefineComponent> => {
		debug.adapter('vue', 'Resolving component', name)

		if (options.resolve) {
			const component = await options.resolve?.(name)
			return component.default ?? component
		}

		if (options.components) {
			return await resolvePageComponent(name, options)
		}

		throw new Error('Either `initializeHybridly#resolve` or `initializeHybridly#pages` should be defined.')
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
 * Resolves a page component.
 */
export async function resolvePageComponent(name: string, options: ResolvedInitializeOptions) {
	const components = options.components!

	if (name.includes(':')) {
		const [domain, page] = name.split(':')
		name = `${options.domains}.${domain}.${options.pages}.${page}`
	}

	const path = Object.keys(components)
		.sort((a, b) => a.length - b.length)
		.find((path) => path.endsWith(`${name.replaceAll('.', '/')}.vue`))

	if (!path) {
		showPageComponentErrorModal(name)
		console.warn(`Page component "${name}" could not be found. Available pages:`, Object.keys(components))

		return
	}

	let component = typeof components[path] === 'function'
		? await components[path]()
		: components[path]

	component = component.default ?? component

	return component
}

type ResolvedInitializeOptions = InitializeOptions & HybridlyConfig

interface InitializeOptions {
	/** Callback that gets executed before Vue is mounted. */
	enhanceVue?: (vue: App<Element>) => any
	/** ID of the app element. */
	id?: string
	/** Clean up the host element's payload dataset after loading. */
	cleanup?: boolean
	/** Whether to set up the devtools plugin. */
	devtools?: boolean
	/** A custom component resolution option. */
	resolve?: ResolveComponent
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
	/** Initial view data. This is automatically set by Laravel, using this option would override the default behavior. */
	payload?: HybridPayload
	/** A custom collection of pages components. This is automatically determined thanks to `root` and `pages`, using this would override the default behavior. */
	components?: Record<string, any>
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
