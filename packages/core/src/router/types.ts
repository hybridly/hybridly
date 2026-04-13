import type { RequestData } from '@hybridly/utils'
import type { CloseDialogOptions } from '../dialog'
import type { HybridlyError } from '../errors'
import type { HttpError, HttpResponse, HttpUploadProgressEvent } from '../http'
import type { MountedHookOptions, RequestHooks } from '../plugins/hooks'
import type { RouteName, RouteParameters } from '../routing/types'
import type { UrlResolvable, UrlTransformable } from '../url'

export type ConditionalNavigationOption<T extends boolean | string> =
	| T
	| ((payload: NavigationOptions) => T)

export interface ComponentNavigationOptions {
	/** Dialog data. */
	dialog?: Dialog | false
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
	/** Whether to preserve the current view component state. */
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
	/** Whether to preserve the scrollbars positions on the view. */
	preserveScroll?: ConditionalNavigationOption<boolean>
	/** Whether to preserve the current view component's state. */
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
	 * @internal
	 */
	updateHistoryState?: boolean
	/**
	 * Defines the type of view transition to use. If a string or array is given, uses it as the transition type.
	 *
	 * @see https://developer.mozilla.org/en-US/docs/Web/API/View_Transition_API/Using_types
	 */
	viewTransition?: string | string[] | boolean
}

export interface InternalNavigationOptions extends NavigationOptions {
	/**
	 * Defines the kind of navigation being performed.
	 * - initial: the initial load's navigation
	 * - server: a navigation initiated by a server round-trip
	 * - local: a navigation initiated by `router.local`
	 * - back-forward: a navigation initiated by the browser's `popstate` event
	 * @internal
	 */
	type: 'initial' | 'local' | 'back-forward' | 'server'
	/**
	 * Defines whether this navigation opens a dialog.
	 * @internal
	 */
	hasDialog?: boolean
	/**
	 * Final properties object for the view.
	 * @internal
	 */
	properties?: Properties
}

export type Method = 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE'
export type RequestMode = 'navigation' | 'async'
export type AsyncInterruptionScope = 'none' | 'all' | 'same-group'

export interface HybridRequestOptions extends Omit<NavigationOptions, 'payload'> {
	/** The URL to navigation. */
	url?: UrlResolvable
	/** Defines how this request should be executed. */
	mode?: RequestMode
	/**
	 * Group identifier used to interrupt asynchronous requests.
	 * @see interruptAsyncOnStart
	 */
	group?: string
	/** Whether this asynchronous request should be interrupted whenever a new navigation request starts. */
	cancelOnNavigation?: boolean
	/**
	 * Defines which asynchronous requests should be interrupted when this request starts.
	 * `none` (default): does not interrupt any request
	 * `same-group` (default if `group` is specified): interrupts requests that share the same group identifier
	 * `all`: interrupts all asynchronous requests
	 */
	interruptAsyncOnStart?: AsyncInterruptionScope
	/** HTTP verb to use for the request. */
	method?: Method | Lowercase<Method>
	/** Body of the request. */
	data?: RequestData
	/** Which properties to update for this navigation. Other properties will be ignored. */
	only?: string | string[]
	/** Which properties not to update for this navigation. Other properties will be updated. */
	except?: string | string[]
	/** Which properties to clear before the update. */
	reset?: string | string[]
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
	 * @see https://laravel.com/docs/master/routing#form-method-spoofing
	 */
	spoof?: boolean
	/** If `false`, does not trigger the progress bar for this request. */
	progress?: boolean
	/** Abort controller for this request. */
	abortController?: AbortController
}

export interface NavigationResponse {
	response?: HttpResponse
	error?: Error
}

export interface DialogRouter {
	/** Closes the current dialog. */
	close: (options?: CloseDialogOptions) => void
}

export interface Router {
	abort: () => void
	/** Makes a navigate with the given options. */
	navigate: (options: HybridRequestOptions) => Promise<NavigationResponse>
	/** Reloads the current page. */
	reload: (options?: HybridRequestOptions) => Promise<NavigationResponse>
	/** Makes a request to given named route. The HTTP verb is determined automatically but can be overriden. */
	to: <T extends RouteName>(
		name: T,
		parameters?: RouteParameters<T>,
		options?: Omit<HybridRequestOptions, 'url'>,
	) => Promise<NavigationResponse>
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
	/** Determines if the given route name and parameters matches the current route. */
	matches: <T extends RouteName>(name: T, parameters?: RouteParameters<T>) => boolean
	/** Gets the current route name. Returns `undefined` is unknown. */
	current: () => RouteName | undefined
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

/** A hybrid request being made. */
export interface PendingHybridRequest {
	/** The URL to which the request is being made. */
	url: URL
	/** Abort controller associated to this request. */
	controller: AbortController
	/** Options for the associated hybrid request. */
	options: HybridRequestOptions
	/** Navigation identifier. */
	id: string
	/** Whether the request has completed. */
	completed: boolean
	/** Whether the request has been gracefully interrupted. */
	cancelled: boolean
	/** Whether the request has been forcefully interrupted. */
	interrupted: boolean
	/** Promise for the request. */
	promise: Promise<NavigationResponse>
	/** Callback that resolves the request promise. */
	resolve: (response: NavigationResponse) => void
	/** The view from which the request has started. */
	view: View
}

/*
|--------------------------------------------------------------------------
| View
|--------------------------------------------------------------------------
*/

/** A view or dialog component. */
export interface View {
	/** Name of the component to use. */
	component?: string
	/** Properties to apply to the component. */
	properties: Properties
	/** Deferred properties for this view. */
	deferred: Record<string, string | string[]>
	/** Properties that should be merged with the existing payload. */
	mergeable: MergeableProperty[]
}

export interface Dialog extends Required<View> {
	/** URL that is the base background view when navigating to the dialog directly. */
	baseUrl: string
	/** URL to which the dialog should redirect when closed. */
	redirectUrl: string
	/** Unique identifier for this modal's lifecycle. */
	key: string
}

export type MergeableProperty = [
	string, // property name
	boolean, // true = prepend, false = append
	string | null,
	string[], // merge paths
]

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
	/** On mounted callback. */
	onMounted?: (options: MountedHookOptions) => void
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
	/** Validation errors grouped by error bag. */
	validation: Validation
	/** The current page URL. */
	url: string
	/** The current asset version. */
	version: string
}

export interface Progress {
	/** Base event. */
	event: HttpUploadProgressEvent
	/** Computed percentage. */
	percentage: Readonly<number>
}

export type Errors = Record<string, any>
export type Validation = Record<string, Errors>
