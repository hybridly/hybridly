import { ResolveComponent, RouterRequest, SwapDialog, SwapView, View } from '../types'
import { debug } from '../utils'
import { makeUrl } from './url'

/** Creates a new context for the router. */
export function createContext(options: RouterContextOptions): RouterContext {
	return {
		...options.request,
		url: makeUrl(options.request.url).toString(),
		adapter: options.adapter,
	}
}

/** Mutates the given context. */
export function setContext(context: RouterContext, merge: Partial<RouterContext> = {}): void {
	Object.keys(merge).forEach((key) => {
		Reflect.set(context, key, merge[key as keyof RouterContext])
	})

	debug.context('Updated context:', { context, added: merge })
}

/** Gets a request from the current context. */
export function requestFromContext(context: RouterContext): RouterRequest {
	return {
		url: context.url,
		version: context.version,
		view: context.view,
		dialog: context.dialog,
	}
}

/** Options for creating a router context. */
export interface RouterContextOptions {
	/** The initial request served by the browser. */
	request: RouterRequest
	/** Adapter-specific functions. */
	adapter: Adapter
}

/** Router context. */
export interface RouterContext {
	/** The current, normalized URL. */
	url: string
	/** The current view. */
	view: View
	/** The current, optional dialog. */
	dialog?: View
	/** The current local asset version. */
	version: string
	/** The current adapter's functions. */
	adapter: Adapter
}

/** Adapter-specific functions. */
export interface Adapter {
	/** Resolves a component from the given name. */
	resolveComponent: ResolveComponent
	/** Swaps to the given view. */
	swapView: SwapView
	/** Swaps to the given dialog. */
	swapDialog: SwapDialog
}
