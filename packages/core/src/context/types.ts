import type { PendingVisit, ResolveComponent, SwapDialog, SwapView, View, VisitPayload } from '../router'

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
export interface InternalRouterContext {
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
	/** History state serializer. */
	serializer: Serializer
}

/** Router context. */
export type RouterContext = Readonly<InternalRouterContext>

/** Adapter-specific functions. */
export interface Adapter {
	/** Resolves a component from the given name. */
	resolveComponent: ResolveComponent
	/** Swaps to the given view. */
	swapView: SwapView
	/** Swaps to the given dialog. */
	swapDialog: SwapDialog
	/** Called when the context is updated. */
	update?: (context: InternalRouterContext) => void
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
