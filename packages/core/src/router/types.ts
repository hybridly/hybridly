import type { RequestData } from '@hybridly/utils'
import type { AxiosProgressEvent, AxiosResponse } from 'axios'
import type { Hooks } from '../plugins/hooks'
import type { UrlResolvable, UrlTransformable } from '../url'

export type ConditionalNavigationOption =
	| boolean
	| ((payload: HybridPayload) => boolean)

export interface ComponentNavigationOptions {
	/** Name of the component to use. */
	component?: string
	/** Properties to apply to the component. */
	properties: Properties
	/**
	 * Whether to replace the current history state instead of adding
	 * one. This affects the browser's "back" and "forward" features.
	 */
	replace?: ConditionalNavigationOption
	/** Whether to preserve the current scrollbar position. */
	preserveScroll?: ConditionalNavigationOption
	/** Whether to preserve the current page component state. */
	preserveState?: ConditionalNavigationOption
}

export interface NavigationOptions {
	/** View to navigate to. */
	payload?: HybridPayload
	/**
	 * Whether to replace the current history state instead of adding
	 * one. This affects the browser's "back" and "forward" features.
	 */
	replace?: ConditionalNavigationOption
	/** Whether to preserve the current scrollbar position. */
	preserveScroll?: ConditionalNavigationOption
	/** Whether to preserve the current page component's state. */
	preserveState?: ConditionalNavigationOption
	/** Whether to preserve the current URL. */
	preserveUrl?: ConditionalNavigationOption
	/**
	 * Properties of the given URL to override.
	 * @example
	 * ```ts
	 * router.get('/login?redirect=/', {
	 * 	transformUrl: { search: '' }
	 * }
	 * ```
	 */
	transformUrl?: UrlTransformable | ((url: string) => UrlTransformable)
	/**
	 * Defines whether the history state should be updated.
	 * @internal This is an advanced property meant to be used internally.
	 */
	updateHistoryState?: boolean
	/**
	 * Defines whether this navigation is a back/forward navigation from the popstate event.
	 * @internal This is an advanced property meant to be used internally.
	 */
	isBackForward?: boolean
}

export type Method = 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE'

export interface HybridRequestOptions extends Omit<NavigationOptions, 'payload'> {
	/** The URL to navigation. */
	url?: UrlResolvable
	/** HTTP verb to use for the request. */
	method?: Method
	/** Body of the request. */
	data?: RequestData
	/** Which properties to update for this navigation. Other properties will be ignored. */
	only?: string | string[]
	/** Which properties not to update for this navigation. Other properties will be updated. */
	except?: string | string[]
	/** Specific headers to add to the request. */
	headers?: Record<string, string>
	/** The bag in which to put potential errors. */
	errorBag?: string
	/** Hooks for this navigation. */
	hooks?: Partial<Hooks>
	/** If `true`, force the usage of a `FormData` object. */
	useFormData?: boolean
}

export interface NavigationResponse {
	response?: AxiosResponse
	error?: {
		type: string
		actual: Error
	}
}

export interface Router {
	/** Aborts the currently pending navigate, if any. */
	abort: () => Promise<void>
	/** Checks if there is an active navigate. */
	active: () => boolean
	/** Makes a navigate with the given options. */
	navigate: (options: HybridRequestOptions) => Promise<NavigationResponse>
	/** Reloads the current page. */
	reload: (options?: HybridRequestOptions) => Promise<NavigationResponse>
	/** Makes a GET request to the given URL. */
	get: (url: UrlResolvable, options?: Omit<HybridRequestOptions, 'method' | 'url'>) => Promise<NavigationResponse>
	/** Makes a POST request to the given URL. */
	post: (url: UrlResolvable, options?: Omit<HybridRequestOptions, 'method' | 'url'>) => Promise<NavigationResponse>
	/** Makes a PUT request to the given URL. */
	put: (url: UrlResolvable, options?: Omit<HybridRequestOptions, 'method' | 'url'>) => Promise<NavigationResponse>
	/** Makes a PATCH request to the given URL. */
	patch: (url: UrlResolvable, options?: Omit<HybridRequestOptions, 'method' | 'url'>) => Promise<NavigationResponse>
	/** Makes a DELETE request to the given URL. */
	delete: (url: UrlResolvable, options?: Omit<HybridRequestOptions, 'method' | 'url'>) => Promise<NavigationResponse>
	/** Navigates to the given external URL. Convenience method using `document.location.href`. */
	external: (url: UrlResolvable, data?: HybridRequestOptions['data']) => void
	/** Navigates to the given URL without a server round-trip. */
	local: (url: UrlResolvable, options: ComponentNavigationOptions) => Promise<void>
	/** Access the history state. */
	history: {
		/** Remembers a value for the given route. */
		remember: (key: string, value: any) => void
		/** Gets a remembered value. */
		get: <T = any>(key: string) => T | undefined
	}
}

/** A navigation being made. */
export interface PendingNavigation {
	/** The URL to which the request is being made. */
	url: URL
	/** Abort controller associated to this request. */
	controller: AbortController
	/** Options for the associated hybrid request. */
	options: HybridRequestOptions
	/** Navigation identifier. */
	id: string
}

/*
|--------------------------------------------------------------------------
| View
|--------------------------------------------------------------------------
*/

/** A page or dialog component. */
export interface View {
	/** Name of the component to use. */
	name: string
	/** Properties to apply to the component. */
	properties: Properties
}

export type Property =
	| null
	| string
	| number
	| boolean
	| Property[]
	| { [name: string]: Property }
export type Properties = Record<string | number, Property>

/*
|--------------------------------------------------------------------------
| Swap
|--------------------------------------------------------------------------
*/

export interface SwapOptions<T> {
	/** The new component. */
	component: T
	/** Whether to preserve the state of the component. */
	preserveState?: boolean
}

export type ViewComponent = any
export type DialogComponent = any
export type ResolveComponent = (name: string) => Promise<ViewComponent>
export type SwapView = (options: SwapOptions<ViewComponent>) => Promise<void>
export type SwapDialog = (options: SwapOptions<DialogComponent>) => Promise<void>

/*
|--------------------------------------------------------------------------
| Request
|--------------------------------------------------------------------------
*/

/** The payload of a navigation request from the server. */
export interface HybridPayload {
	/** The view to use in this request. */
	view: View
	/** An optional dialog. */
	dialog?: View
	/** The current page URL. */
	url: string
	/** The current asset version. */
	version: string
}

export interface Progress {
	/** Base event. */
	event: AxiosProgressEvent
	/** Computed percentage. */
	percentage: Readonly<number>
}

export interface Errors {
	[key: string]: string
}
