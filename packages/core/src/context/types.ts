import type { Axios } from 'axios'
import type { Hooks } from '../plugins/hooks'
import type { Plugin } from '../plugins/plugin'
import type { RoutingConfiguration } from '../route/types'
import type { PendingNavigation, ResolveComponent, SwapDialog, SwapView, View, HybridPayload } from '../router'

/** Options for creating a router context. */
export interface RouterContextOptions {
	/** The initial payload served by the browser. */
	payload: HybridPayload
	/** Adapter-specific functions. */
	adapter: Adapter
	/** History state serializer. */
	serializer?: Serializer
	/** List of plugins. */
	plugins?: Plugin[]
	/** The Axios instance. */
	axios?: Axios
	/** Initial routing configuration. */
	routing?: RoutingConfiguration
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
	adapter: ResolvedAdapter
	/** Scroll positions of the current page's DOM elements. */
	scrollRegions: ScrollRegion[]
	/** Arbitrary state. */
	state: Record<string, any>
	/** Currently pending navigation. */
	pendingNavigation?: PendingNavigation
	/** History state serializer. */
	serializer: Serializer
	/** List of plugins. */
	plugins: Plugin[]
	/** Global hooks. */
	hooks: Partial<Record<keyof Hooks, Array<Function>>>
	/** The Axios instance. */
	axios: Axios
	/** Routing configuration. */
	routing?: RoutingConfiguration
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

export interface ResolvedAdapter extends Adapter{
	updateRoutingConfiguration: (routing?: RoutingConfiguration) => void
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
