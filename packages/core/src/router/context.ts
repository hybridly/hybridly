import { debug } from '@hybridly/utils'
import { createEmitter, Emitter, VisitEvents } from '../events'
import { ResolveComponent, VisitPayload, SwapDialog, SwapView, View } from '../types'
import { createSerializer } from './history'
import { VisitOptions } from './router'
import { makeUrl } from './url'

/** Creates a new context for the router. */
export function createContext(options: RouterContextOptions): RouterContext {
	return {
		...options.payload,
		events: createEmitter(),
		serializer: createSerializer(options),
		url: makeUrl(options.payload.url).toString(),
		adapter: options.adapter,
		scrollRegions: [],
		state: {},
	}
}

/** Mutates the given context. */
export function setContext(context: RouterContext, merge: Partial<RouterContext> = {}, options: SetContextOptions = {}): void {
	Object.keys(merge).forEach((key) => {
		Reflect.set(context, key, merge[key as keyof RouterContext])
	})

	if (options.propagate !== false) {
		context.adapter.update?.(context)
	}

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
	/** History state serializer. */
	serializer?: Serializer
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
	/** Scroll positions of the current page's DOM elements. */
	scrollRegions: ScrollRegion[]
	/** Arbitrary state. */
	state: Record<string, any>
	/** Currently pending visit. */
	activeVisit?: PendingVisit
	/** Event emitter for this context. */
	events: Emitter<VisitEvents>
	/** History state serializer. */
	serializer: Serializer
}

/** Adapter-specific functions. */
export interface Adapter {
	/** Resolves a component from the given name. */
	resolveComponent: ResolveComponent
	/** Swaps to the given view. */
	swapView: SwapView
	/** Swaps to the given dialog. */
	swapDialog: SwapDialog
	/** Called when the context is updated. */
	update?: (context: RouterContext) => void
}

/** An axios visit being made. */
export interface PendingVisit {
	/** The URL to which the request is being made. */
	url: URL
	/** Abort controller associated to this request. */
	controller: AbortController
	/** Options for the associated visit. */
	options: VisitOptions
	/** Visit identifier. */
	id: string
}

export interface ScrollRegion {
	top: number
	left: number
}

/** Provides methods to serialize the state into the history state. */
export interface Serializer {
	serialize: <T>(view: T) => any
	unserialize: <T>(state: any) => T
}

export interface SetContextOptions {
	/** Whether to propagate the context to adapters. */
	propagate?: boolean
}
