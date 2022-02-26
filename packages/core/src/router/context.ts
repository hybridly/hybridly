import { ResolveComponent, VisitPayload, SwapDialog, SwapView, View } from '../types'
import { debug } from '../utils'
import { makeUrl } from './url'

/** Creates a new context for the router. */
export function createContext(options: RouterContextOptions): RouterContext {
	return {
		...options.payload,
		url: makeUrl(options.payload.url).toString(),
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

/** Gets a visit payload from the current context. */
export function payloadFromContext(context: RouterContext): VisitPayload {
	return {
		url: context.url,
		version: context.version,
		view: context.view,
		dialog: context.dialog,
	}
}

/** Options for creating a router context. */
export interface RouterContextOptions {
	/** The initial payload served by the browser. */
	payload: VisitPayload
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
