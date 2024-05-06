import type { App, DefineComponent, Plugin as VuePlugin } from 'vue'
import { createApp, h } from 'vue'
import type { DynamicConfiguration, Plugin, RouterContext, RouterContextOptions } from '@hybridly/core'
import { createRouter } from '@hybridly/core'
import { showViewComponentErrorModal, debug, random } from '@hybridly/utils'
import type { Axios } from 'axios'
import { type ProgressOptions, progress } from './plugins/progress'
import { wrapper } from './components/wrapper'
import { state } from './stores/state'
import { devtools } from './devtools'
import { dialogStore } from './stores/dialog'
import { onMountedCallbacks } from './stores/mount'
import { viewTransition } from './plugins/view-transition'
import {initResources} from "./composables/resource";

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
		routing: resolved.routing,
		adapter: {
			resolveComponent: resolve,
			executeOnMounted: (callback) => {
				onMountedCallbacks.push(callback)
			},
			onDialogClose: async() => {
				dialogStore.hide()
			},
			onContextUpdate: (context) => {
				state.setContext(context)
				initResources()
			},
			onViewSwap: async(options) => {
				if (options.component) {
					onMountedCallbacks.push(() => options.onMounted?.({ isDialog: false }))
					state.setView(options.component)
				}

				state.setProperties(options.properties)

				if (!options.preserveState && !options.dialog) {
					state.setViewKey(random())
				}

				if (options.dialog) {
					onMountedCallbacks.push(() => options.onMounted?.({ isDialog: true }))
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

	const render = () => h(wrapper as any)

	if (options.setup) {
		return await options.setup({
			element,
			wrapper,
			render,
			hybridly: devtools,
			props: { context: state.context.value! },
			payload,
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

		if (!options.imported) {
			throw new Error('No component loaded. Did you initialize Hybridly? Does `php artisan hybridly:config` return an error?')
		}

		return await resolveViewComponent(name, options)
	}

	options.plugins ??= []

	if (options.progress !== false) {
		options.plugins.push(progress(typeof options.progress === 'object' ? options.progress : {}))
	}

	if (options.viewTransition !== false) {
		options.plugins.push(viewTransition())
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
	const components = options.imported!
	const result = options.components.views.find((view) => name === view.identifier)
	const path = Object.keys(components)
		.sort((a, b) => a.length - b.length)
		.find((path) => result ? path.endsWith(result?.path) : false)

	if (!result || !path) {
		console.warn(`View component [${name}] not found. Available components: `, options.components.views.map(({ identifier }) => identifier))
		showViewComponentErrorModal(name)
		return
	}

	let component = typeof components[path] === 'function'
		? await components[path]()
		: components[path]

	component = component.default ?? component

	return component
}

type ResolvedInitializeOptions = InitializeOptions & DynamicConfiguration & {
	/** List of components imported by `import.meta.glob`. */
	imported: Record<string, any>
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
	/**
	 * Enables the View Transition API, if supported.
	 * @see https://developer.mozilla.org/en-US/docs/Web/API/ViewTransition
	 */
	viewTransition?: boolean
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
	/** Initial payload. */
	payload: Record<string, any>
}
