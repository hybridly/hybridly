import type { Axios } from 'axios'
import type { Hooks } from '../plugins/hooks'
import type { Plugin } from '../plugins/plugin'
import type { RoutingConfiguration } from '../routing/types'
import type { PendingNavigation, ResolveComponent, SwapView, View, HybridPayload, Dialog } from '../router'

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
	/** Whether to display response error modals. */
	responseErrorModals?: boolean
}

/** Router context. */
export interface InternalRouterContext {
	/** The current, normalized URL. */
	url: string
	/** The current view. */
	view: View
	/** The current, optional dialog. */
	dialog?: Dialog
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
	/** Whether to display response error modals. */
	responseErrorModals?: boolean
}

/** Router context. */
export type RouterContext = Readonly<InternalRouterContext>

/** Adapter-specific functions. */
export interface Adapter {
	/** Resolves a component from the given name. */
	resolveComponent: ResolveComponent
	/** Called when the view is swapped. */
	onViewSwap: SwapView
	/** Called when the context is updated. */
	onContextUpdate?: (context: InternalRouterContext) => void
	/** Called when a dialog is closed. */
	onDialogClose?: (context: InternalRouterContext) => void
	/** Called when Hybridly is waiting for a component to be mounted. The given callback should be executed after the view component is mounted. */
	onWaitingForMount: (callback: Function) => void
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
	serialize: <T>(view: T) => string
	unserialize: <T>(state?: string) => T | undefined
}

export interface SetContextOptions {
	/** Whether to propagate the context to adapters. */
	propagate?: boolean
}
