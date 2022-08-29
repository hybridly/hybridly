import { AxiosResponse } from 'axios'
import { Hooks } from '../plugins/hooks'
import { UrlResolvable, UrlTransformable } from '../url'

export type ConditionalNavigationOption = boolean | ((payload: VisitPayload) => boolean)

export interface LocalVisitOptions {
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
	payload?: VisitPayload
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
	transformUrl?: UrlTransformable
	/**
	 * Defines whether the history state should be updated.
	 * @internal This is an advanced property meant to be used internally.
	 */
	updateHistoryState?: boolean
	/**
	 * Defines whether this navigation is a back/forward visit from the popstate event.
	 * @internal This is an advanced property meant to be used internally.
	 */
	isBackForward?: boolean
}

export type Method = 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE'

export interface VisitOptions extends Omit<NavigationOptions, 'request'> {
	/** The URL to visit. */
	url?: UrlResolvable
	/** HTTP verb to use for the request. */
	method?: Method
	/** Body of the request. */
	data?: RequestData
	/** Which properties to update for this visit. Other properties will be ignored. */
	only?: string | string[]
	/** Which properties not to update for this visit. Other properties will be updated. */
	except?: string | string[]
	/** Specific headers to add to the request. */
	headers?: Record<string, string>
	/** The bag in which to put potential errors. */
	errorBag?: string
	/** Hooks for this visit. */
	hooks?: Partial<Hooks>
}

export interface VisitResponse {
	response?: AxiosResponse
	error?: {
		type: string
		actual: Error
	}
}

export interface Router {
	/** Aborts the currently pending visit, if any. */
	abort: () => Promise<void>
	/** Checks if there is an active request. */
	active: () => boolean
	/** Makes a visit with the given options. */
	visit: (options: VisitOptions) => Promise<VisitResponse>
	/** Reloads the current page. */
	reload: (options?: VisitOptions) => Promise<VisitResponse>
	/** Makes a GET request to the given URL. */
	get: (url: UrlResolvable, options?: Omit<VisitOptions, 'method' | 'url'>) => Promise<VisitResponse>
	/** Makes a POST request to the given URL. */
	post: (url: UrlResolvable, options?: Omit<VisitOptions, 'method' | 'url'>) => Promise<VisitResponse>
	/** Makes a PUT request to the given URL. */
	put: (url: UrlResolvable, options?: Omit<VisitOptions, 'method' | 'url'>) => Promise<VisitResponse>
	/** Makes a PATCH request to the given URL. */
	patch: (url: UrlResolvable, options?: Omit<VisitOptions, 'method' | 'url'>) => Promise<VisitResponse>
	/** Makes a DELETE request to the given URL. */
	delete: (url: UrlResolvable, options?: Omit<VisitOptions, 'method' | 'url'>) => Promise<VisitResponse>
	/** Navigates to the given external URL. Alias for `document.location.href`. */
	external: (url: UrlResolvable, data?: VisitOptions['data']) => void
	/** Navigates to the given URL without a server round-trip. */
	local: (url: UrlResolvable, options: LocalVisitOptions) => Promise<void>
	/** Access the history state. */
	history: {
		/** Remembers a value for the given route. */
		remember: (key: string, value: any) => void
		/** Gets a remembered value. */
		get: <T = any>(key: string) => T | undefined
	}
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

export type Property = null | string | number | boolean | Property[] | { [name: string]: Property }
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

/** The payload of a visit request from the server. */
export interface VisitPayload {
	/** The view to use in this request. */
	view: View
	/** An optional dialog. */
	dialog?: View
	/** The current page URL. */
	url: string
	/** The current asset version. */
	version: string
}

export type RequestData = FormDataValue | FormData

type FormDataObject = { [Key in string]: FormDataValue }
type FormDataPrimitive = Blob | Date | boolean | number | File | string | null
type FormDataArray = FormDataValue[]
type FormDataValue = FormDataObject | FormDataPrimitive | FormDataArray

export interface Progress {
	/** Base event. */
	event: ProgressEvent
	/** Computed percentage. */
	percentage: Readonly<number>
}

export interface Errors {
	[key: string]: string
}
