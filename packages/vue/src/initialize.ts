import { DefineComponent, h } from 'vue'
import { debug, createRouter, VisitPayload, ResolveComponent, RouterContext, RouterContextOptions } from '@hybridly/core'
import { showPageComponentErrorModal } from '@hybridly/utils'
import { wrapper } from './components/wrapper'
import { state } from './stores/state'
import { initializeProgress, ProgressOptions } from './progress'

export async function initializeHybridly(options: HybridlyOptions) {
	const { element, payload, resolve } = prepare(options)

	if (!element) {
		throw new Error('Could not find an HTML element to initialize Vue on.')
	}

	if (!payload) {
		throw new Error('No payload. Are you using `@hybridly` or the `payload` option?')
	}

	state.setView(await resolve(payload.view.name))
	state.setContext(await createRouter({
		serializer: options.serializer,
		adapter: {
			resolveComponent: resolve,
			swapDialog: async() => {},
			swapView: async(options) => {
				state.setView(options.component)

				if (!options.preserveState) {
					state.setViewKey(Date.now())
				}
			},
			update: (context) => {
				debug.adapter('vue:update', 'Updating context:', { context })
				state.setContext(context)
			},
		},
		payload,
	}))

	await options.setup({
		element,
		wrapper,
		props: { context: state.context.value! },
		render: () => h(wrapper as any, { context: state.context.value }),
	})

	if (options.progress !== false) {
		initializeProgress(
			state.context.value!,
			typeof options.progress === 'object'
				? options.progress
				: {},
		)
	}
}

function prepare(options: HybridlyOptions) {
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

		if (options.pages) {
			return await resolvePageComponent(name, options.pages, options.layout)
		}

		throw new Error('Either `initializeHybridly#resolve` or `initializeHybridly#pages` should be defined.')
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
export async function resolvePageComponent(name: string, pages: Record<string, any>, defaultLayout?: any) {
	const path = Object.keys(pages)
		.sort((a, b) => a.length - b.length)
		.find((path) => path.endsWith(`${name.replaceAll('.', '/')}.vue`))

	if (!path) {
		showPageComponentErrorModal(name)
		console.warn(`Page component "${name} could not be found. Available pages:`, Object.keys(pages))

		return
	}

	let component = typeof pages[path] === 'function'
		? await pages[path]()
		: pages[path]

	component = component.default ?? component
	component.layout ??= defaultLayout

	return component
}

interface HybridlyOptions {
	/** ID of the app element. */
	id?: string
	/** Initial view data. */
	payload?: VisitPayload
	/** A collection of pages. */
	pages?: Record<string, any>
	/** An optional default persistent layout. */
	layout?: any
	/** A custom component resolution option. */
	resolve?: ResolveComponent
	/** Custom history state serialization functions. */
	serializer?: RouterContextOptions['serializer']
	/** Clean up the host element's payload dataset after loading. */
	cleanup?: boolean
	/** Progressbar options. */
	progress?: boolean | Partial<ProgressOptions>
	/** Sets up the hybridly router. */
	setup: (options: SetupArguments) => any
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
	/** Renders the wrapper. */
	render: () => ReturnType<typeof h>
}
