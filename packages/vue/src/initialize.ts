import { ComponentOptions, DefineComponent, h } from 'vue'
import { RouterRequest, ResolveComponent, createRouter } from '@sleightful/core'
import { Promisable } from 'type-fest'
import { wrapper } from './components/wrapper'
import { Router, state } from './stores/state'
// import { plugin } from './plugin'

export async function initializeSleightful(options: SleightfulOptions) {
	const { element, request, resolve } = prepare(options)

	if (!element) {
		throw new Error('Could not find an HTML element to initialize Vue on.')
	}

	const router = await createRouter({
		adapter: {
			resolveComponent: resolve,
			swapDialog: async() => {},
			swapView: async(options) => {
				state.setComponent(options.component)
				// state.preserveState(options.preserveState)
			},
		},
		request,
	})

	const component = await resolve(request.view.name)
	await options.setup({
		element,
		wrapper,
		props: { router, component },
		render: () => h(wrapper as any, { router, component }),
		// plugin,
	})
}

function prepare(options: SleightfulOptions) {
	const isServer = typeof window === 'undefined'
	const element = document?.getElementById(options.id ?? 'app') ?? undefined
	const request = options.view ?? JSON.parse(element?.dataset.page || '{}')
	const resolve = async(name: string): Promise<DefineComponent> => {
		if (options.resolve) {
			const component = await options.resolve?.(name)
			return component.default ?? component
		}

		if (options.pages) {
			return await resolvePageComponent(name, options.pages, options.layout)
		}

		throw new Error('Either `resolve` or `pages` should be defined.')
	}

	return {
		isServer,
		element,
		request,
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
	view?: RouterRequest
	/** A collection of pages. */
	pages?: Record<string, any>
	/** An optional default persistent layout. */
	layout?: any
	/** A custom component resolution option. */
	resolve?: ResolveComponent
	/** Sets up the sleightful router. */
	setup: (options: SetupOptions) => Promisable<void>
}

interface SetupOptions {
	/** DOM element to mount Vue on. */
	element: Element
	/** Sleightful wrapper component. */
	wrapper: any
	/** Sleightful wrapper component properties. */
	props: {
		router: Router
		component: ComponentOptions
	}
	/** Renders the wrapper. */
	render: () => ReturnType<typeof h>
}
