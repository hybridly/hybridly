import { DefineComponent, h, Plugin as VuePlugin } from 'vue'
import { createRouter, VisitPayload, ResolveComponent, RouterContext, RouterContextOptions, Plugin } from '@monolikit/core'
import { showPageComponentErrorModal, debug } from '@monolikit/utils'
import { progress, ProgressOptions } from '@monolikit/progress-plugin'
import { wrapper } from './components/wrapper'
import { state } from './stores/state'
import { plugin } from './devtools'
import { RouteCollection } from './routes'

export async function initializeMonolikit(options: MonolikitOptions) {
	const { element, payload, resolve } = prepare(options)

	if (!element) {
		throw new Error('Could not find an HTML element to initialize Vue on.')
	}

	if (!payload) {
		throw new Error('No payload. Are you using `@monolikit` or the `payload` option?')
	}

	// This causes an issue on first load, the same component renders twice.
	// Not sure of the side effects so let's keep it commented for now.
	// state.setView(await resolve(payload.view.name))
	state.setContext(await createRouter({
		plugins: options.plugins,
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
				state.setContext(context)
			},
		},
		payload,
	}))

	await options.setup({
		element,
		wrapper,
		monolikit: plugin,
		props: { context: state.context.value! },
		render: () => h(wrapper as any, { context: state.context.value }),
	})
}

function prepare(options: MonolikitOptions) {
	debug.adapter('vue', 'Preparing Monolikit with options:', options)
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

		throw new Error('Either `initializeMonolikit#resolve` or `initializeMonolikit#pages` should be defined.')
	}

	// Using `window` is the only way I found to be able to get the route collection,
	// since this initialization is ran after the Vite plugin is done executing.
	if (typeof window !== 'undefined') {
		const routes = window.monolikit?.routes

		if (routes) {
			state.setRoutes(window.monolikit?.routes)
			window.addEventListener<any>('monolikit:routes', (event: CustomEvent<RouteCollection>) => {
				state.setRoutes(event.detail)
			})
		}
	}

	if (options.progress !== false) {
		options.plugins = [
			progress(typeof options.progress === 'object'
				? options.progress
				: {}),
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
export async function resolvePageComponent(name: string, pages: Record<string, any>, defaultLayout?: any) {
	const path = Object.keys(pages)
		.sort((a, b) => a.length - b.length)
		.find((path) => path.endsWith(`${name.replaceAll('.', '/')}.vue`))

	if (!path) {
		showPageComponentErrorModal(name)
		console.warn(`Page component "${name}" could not be found. Available pages:`, Object.keys(pages))

		return
	}

	let component = typeof pages[path] === 'function'
		? await pages[path]()
		: pages[path]

	component = component.default ?? component
	component.layout ??= defaultLayout

	return component
}

interface MonolikitOptions {
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
	/** Sets up the monolikit router. */
	setup: (options: SetupArguments) => any
	/** List of Monolikit plugins. */
	plugins?: Plugin[]
}

interface SetupArguments {
	/** DOM element to mount Vue on. */
	element: Element
	/** Monolikit wrapper component. */
	wrapper: any
	/** Monolikit wrapper component properties. */
	props: {
		context: RouterContext
	}
	/** Vue plugin that registers the devtools. */
	monolikit: VuePlugin
	/** Renders the wrapper. */
	render: () => ReturnType<typeof h>
}
