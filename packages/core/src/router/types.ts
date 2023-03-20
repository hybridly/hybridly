import type { RequestData } from '@hybridly/utils'
import type { AxiosProgressEvent, AxiosResponse } from 'axios'
import type { RequestHooks } from '../plugins/hooks'
import type { CloseDialogOptions } from '../dialog'
import type { RouteName, RouteParameters } from '../routing/types'
import type { UrlResolvable, UrlTransformable } from '../url'

export type ConditionalNavigationOption<T extends boolean | string> =
	| T
	| ((payload: NavigationOptions) => T)

export interface ComponentNavigationOptions {
	/** Name of the component to use. */
	component?: string
	/** Properties to apply to the component. */
	properties?: Properties
	/**
	 * Whether to replace the current history state instead of adding
	 * one. This affects the browser's "back" and "forward" features.
	 */
	replace?: ConditionalNavigationOption<boolean>
	/** Whether to preserve the current scrollbar position. */
	preserveScroll?: ConditionalNavigationOption<boolean>
	/** Whether to preserve the current page component state. */
	preserveState?: ConditionalNavigationOption<boolean>
}

export interface NavigationOptions {
	/** View to navigate to. */
	payload?: HybridPayload
	/**
	 * Whether to replace the current history state instead of adding
	 * one. This affects the browser's "back" and "forward" features.
	 */
	replace?: ConditionalNavigationOption<boolean>
	/** Whether to preserve the scrollbars positions on the page. */
	preserveScroll?: ConditionalNavigationOption<boolean>
	/** Whether to preserve the current page component's state. */
	preserveState?: ConditionalNavigationOption<boolean>
	/** Whether to preserve the current URL. */
	preserveUrl?: ConditionalNavigationOption<boolean>
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
	method?: Method | Lowercase<Method>
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
	hooks?: Partial<RequestHooks>
	/** If `true`, force the usage of a `FormData` object. */
	useFormData?: boolean
	/**
	 * If `false`, disable automatic form spoofing.
	 * @see https://laravel.com/docs/9.x/routing#form-method-spoofing
	 */
	spoof?: boolean
	/**
	 * If `false`, does not trigger the progress bar for this request.
	 */
	progress?: boolean
}

export interface NavigationResponse {
	response?: AxiosResponse
	error?: {
		type: string
		actual: Error
	}
}

export interface DialogRouter {
	/** Closes the current dialog. */
	close: (options?: CloseDialogOptions) => void
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
	/** Makes a request to given named route. The HTTP verb is determined automatically but can be overriden. */
	to: <T extends RouteName>(name: T, parameters?: RouteParameters<T>, options?: Omit<HybridRequestOptions, 'url'>) => Promise<NavigationResponse>
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
	/** Access the dialog router. */
	dialog: DialogRouter
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
	/** Current status. */
	status: 'pending' | 'success' | 'error'
}

/*
|--------------------------------------------------------------------------
| View
|--------------------------------------------------------------------------
*/

/** A page or dialog component. */
export interface View {
	/** Name of the component to use. */
	component: string
	/** Properties to apply to the component. */
	properties: Properties
}

export interface Dialog extends View {
	/** URL that is the base background page when navigating to the dialog directly. */
	baseUrl: string
	/** URL to which the dialog should redirect when closed. */
	redirectUrl: string
	/** Unique identifier for this modal's lifecycle. */
	key: string
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
	/** The new properties. */
	properties?: any
	/** Whether to preserve the state of the component. */
	preserveState?: boolean
	/** Current dialog. */
	dialog?: Dialog
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
	dialog?: Dialog
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
