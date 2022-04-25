import { ComponentOptions, DefineComponent, h } from 'vue'
import { debug, createRouter, VisitPayload, ResolveComponent, RouterContext } from '@sleightful/core'
import { Promisable } from 'type-fest'
import { wrapper } from './components/wrapper'
import { state } from './stores/state'

export async function initializeSleightful(options: SleightfulOptions) {
	const { element, payload, resolve } = prepare(options)

	if (!element) {
		throw new Error('Could not find an HTML element to initialize Vue on.')
	}

	if (!payload) {
		throw new Error('No payload. Are you using `@sleightful` or the `payload` option?')
	}

	state.setContext(await createRouter({
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

	const component = await resolve(payload.view.name)
	await options.setup({
		element,
		wrapper,
		props: { context: state.context.value!, component },
		render: () => h(wrapper as any, { context: state.context.value, component }),
	})
}

function prepare(options: SleightfulOptions) {
	const isServer = typeof window === 'undefined'
	debug.adapter('vue', 'Preparing Sleightful with options:', options)
	const id = options.id ?? 'root'
	const element = document?.getElementById(id) ?? undefined
	debug.adapter('vue', `Element "${id}" is:`, element)
	const payload = options.payload ?? element?.dataset.payload
		? JSON.parse(element!.dataset.payload!)
		: undefined
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

		throw new Error('Either `initializeSleightful#resolve` or `initializeSleightful#pages` should be defined.')
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
	// eslint-disable-next-line no-restricted-syntax
	for (const path in pages) {
		if (path.endsWith(`${name.replaceAll('.', '/')}.vue`)) {
			let component = typeof pages[path] === 'function'
				? await pages[path]()
				: pages[path]

			component = component.default ?? component
			component.layout ??= defaultLayout

			return component
		}
	}

	throw new Error(`Page not found: ${name}`)
}

interface SleightfulOptions {
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
	/** Sets up the sleightful router. */
	setup: (options: SetupArguments) => Promisable<void>
}

interface SetupArguments {
	/** DOM element to mount Vue on. */
	element: Element
	/** Sleightful wrapper component. */
	wrapper: any
	/** Sleightful wrapper component properties. */
	props: {
		context: RouterContext
		component: ComponentOptions
	}
	/** Renders the wrapper. */
	render: () => ReturnType<typeof h>
}
