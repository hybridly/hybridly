import axios, { AxiosResponse } from 'axios'
import qs from 'qs'
import { ERROR_BAG_HEADER, EXCEPT_DATA_HEADER, EXTERNAL_VISIT_HEADER, ONLY_DATA_HEADER, PARTIAL_COMPONENT_HEADER, HYBRIDLY_HEADER, VERSION_HEADER } from '../constants'
import { showModal } from '../error-modal'
import { NotAHybridlyResponseError, VisitCancelledError } from '../errors'
import { VisitEvents } from '../events'
import type { VisitPayload, RequestData, Errors, Properties } from '../types'
import { debug, match, merge, when } from '../utils'
import { createContext, payloadFromContext, RouterContext, RouterContextOptions, setContext } from './context'
import { handleExternalVisit, isExternalResponse, isExternalVisit, performExternalVisit } from './external'
import { setHistoryState, isBackForwardVisit, handleBackForwardVisit, registerEventListeners, getHistoryState, getKeyFromHistory, remember } from './history'
import { resetScrollPositions, restoreScrollPositions, saveScrollPositions } from './scroll'
import { fillHash, makeUrl, normalizeUrl, sameUrls, UrlResolvable } from './url'

/** Creates the hybridly router. */
export async function createRouter(options: RouterContextOptions): Promise<RouterContext> {
	return await initializeRouter(createContext(options))
}

/**
 * Gets a router that use the context returned by the resolve function.
 * This makes the router reactive to context changes.
 */
export function resolveRouter(resolve: ResolveContext): Router {
	return {
		context: resolve,
		abort: async() => resolve().activeVisit?.controller.abort(),
		visit: async(options) => await visit(resolve(), options),
		reload: async(options) => await visit(resolve(), { preserveScroll: true, preserveState: true, ...options }),
		get: async(url, options = {}) => await visit(resolve(), { ...options, url, method: 'GET' }),
		post: async(url, options = {}) => await visit(resolve(), { preserveState: true, ...options, url, method: 'POST' }),
		put: async(url, options = {}) => await visit(resolve(), { preserveState: true, ...options, url, method: 'PUT' }),
		patch: async(url, options = {}) => await visit(resolve(), { preserveState: true, ...options, url, method: 'PATCH' }),
		delete: async(url, options = {}) => await visit(resolve(), { preserveState: true, ...options, url, method: 'DELETE' }),
		local: async(url, options) => await performLocalComponentVisit(resolve(), url, options),
		external: (url, data = {}) => performLocalExternalVisit(url, data),
		history: {
			get: (key) => getKeyFromHistory(resolve(), key),
			remember: (key, value) => remember(resolve(), key, value),
		},
	}
}

/** Performs every action necessary to make a hybridly visit. */
export async function visit(context: RouterContext, options: VisitOptions): Promise<VisitResponse> {
	debug.router('Making a visit:', { context, options })

	try {
		// Temporarily add the visit-specific events to the context event list
		// so they can be called with `emit`.
		context.events.with(options.events)

		// Before anything else, we fire the "before" event to make sure
		// there was no user-specified handler returning "false".
		if (!context.events.emit('before', options)) {
			debug.router('"before" event returned false, aborting the visit.')
			throw new VisitCancelledError('The visit was cancelled by the global emitter.')
		}

		// TODO form transformations
		// TODO preserveState from history

		// Before making the visit, we need to make sure the scroll positions are
		// saved, so we can restore them later.
		saveScrollPositions(context)

		// A visit is being made, we need to add it to the context so it
		// can be reused later on.
		setContext(context, {
			activeVisit: {
				url: makeUrl(options.url ?? context.url),
				controller: new AbortController(),
				options,
			},
		})

		context.events.emit('start', context)
		debug.router('Making request with axios.')

		const response = await axios.request({
			url: context.activeVisit!.url.toString(),
			method: options.method ?? 'GET',
			data: options.method === 'GET' ? {} : options.data,
			params: options.method === 'GET' ? options.data : {},
			signal: context.activeVisit!.controller.signal,
			headers: {
				...options.headers,
				...when(options.only?.length || options.except?.length, {
					[PARTIAL_COMPONENT_HEADER]: context.view.name,
					...when(options.only, { [ONLY_DATA_HEADER]: JSON.stringify(options.only) }, {}),
					...when(options.except, { [EXCEPT_DATA_HEADER]: JSON.stringify(options.except) }, {}),
				}, {}),
				...when(options.errorBag, { [ERROR_BAG_HEADER]: options.errorBag }, {}),
				...when(context.version, { [VERSION_HEADER]: context.version }, {}),
				// 'X-Hybridly-Context': this.currentState.visit.context,
				[HYBRIDLY_HEADER]: true,
				'X-Requested-With': 'XMLHttpRequest',
				'Accept': 'text/html, application/xhtml+xml',
			},
			validateStatus: () => true,
			onUploadProgress: (event: ProgressEvent) => {
				context.events.emit('progress', {
					event,
					percentage: Math.round(event.loaded / event.total * 100),
				})
			},
		})

		context.events.emit('data', response)

		// An external response is a hybridly response that wants a full page
		// load to a requested URL. It may be the same URL, in which case a
		// full page refresh will be performed.
		if (isExternalResponse(response)) {
			debug.router('The response is explicitely external.')
			await performExternalVisit({
				url: fillHash(context.activeVisit!.url, response.headers[EXTERNAL_VISIT_HEADER]),
				preserveScroll: options.preserveScroll === true,
			})

			return { response }
		}

		// An invalid response is a response that do not declare itself via
		// the protocole header.
		// In such cases, we want to throw to handler it later.
		if (!isHybridlyResponse(response)) {
			throw new NotAHybridlyResponseError(response)
		}

		// At this point, we know the response is hybridly.
		debug.router('The response is hybridly.')
		const payload = response.data as VisitPayload

		// If the visit was asking for specific properties, we ensure that the
		// new request object contains the properties of the current view context,
		// because the back-end sent back only the required properties.
		if ((options.only?.length ?? options.except?.length) && payload.view.name === context.view.name) {
			debug.router(`Merging ${options.only ? '"only"' : '"except"'} properties.`, payload.view.properties)
			payload.view.properties = merge(context.view.properties, payload.view.properties)
			debug.router('Merged properties:', payload.view.properties)
		}

		// If everything was according to the plan, we can make our navigation and
		// update the context. Underlying adapters get the updated data.
		await navigate(context, {
			payload: {
				...payload,
				url: fillHash(context.activeVisit!.url, payload.url),
			},
			preserveScroll: options.preserveScroll === true,
			preserveState: options.preserveState,
			preserveUrl: options.preserveUrl,
			replace: options.replace === true || sameUrls(payload.url, window.location.href),
		})

		// If the new view's properties has errors, userland expects an event
		// with said errors to be emitted. However, errors can be scoped with
		// an error bag, and if the given error bag is missing, the event data
		// will be empty.
		if (Object.keys(context.view.properties.errors ?? {}).length > 0) {
			const errors = (() => {
				if (options.errorBag && typeof context.view.properties.errors === 'object') {
					return (context.view.properties.errors as any)[options.errorBag] ?? {}
				}

				return context.view.properties.errors
			})() as Errors

			debug.router('The request returned validation errors.', errors)
			context.events.emit('error', errors)
		} else {
			context.events.emit('success', payload)
		}

		return { response }
	//
	} catch (error: any) {
		match(error.constructor.name, {
			AbortError: () => {
				debug.router('The request was cancelled.', error)
				context.events.emit('abort', context)
			},
			NotAHybridlyResponseError: () => {
				debug.router('The request was not hybridly.')
				context.events.emit('invalid', error)
				showModal(error.response.data)
			},
			default: () => {
				debug.router('An unknown error occured.', error)
				context.events.emit('exception', error)
			},
		})

		context.events.emit('fail', context)

		return {
			error: {
				type: error.constructor.name,
				actual: error,
			},
		}
	} finally {
		debug.router('Ending visit.')
		context.events.emit('after', context)
		context.events.cleanup()
		setContext(context, { activeVisit: undefined })
	}
}

/** Checks if the response contains a hybridly header. */
export function isHybridlyResponse(response: AxiosResponse): boolean {
	return !!response?.headers[HYBRIDLY_HEADER]
}

/**
 * Makes an internal navigation that swaps the view and updates the context.
 * @internal This function is meant to be used internally.
 */
export async function navigate(context: RouterContext, options: NavigationOptions) {
	debug.router('Making an internal navigation:', { context, options })

	// If no request was given, we use the current context instead.
	options.payload ??= payloadFromContext(context)

	const evaluateConditionalOption = (option?: ConditionalNavigationOption) => typeof option === 'function'
		? option(options.payload!)
		: option

	const shouldPreserveState = evaluateConditionalOption(options.preserveState)
	const shouldPreserveScroll = evaluateConditionalOption(options.preserveScroll)
	const shouldReplaceHistory = evaluateConditionalOption(options.replace)

	// If the visit was asking to preserve the current state, we also need to
	// update the context's state from the history state.
	if (shouldPreserveState && getHistoryState(context) && options.payload.view.name === context.view.name) {
		setContext(context, { state: getHistoryState(context) })
	}

	// If the visit required the URL to be preserved, we skip its update
	// by replacing the payload URL with the current context URL.
	if (options.preserveUrl) {
		debug.router(`Preserving the current URL (${context.url}) instead of navigating to ${options.payload.url}`)
		options.payload!.url = context.url
	}

	// We merge the new request into the current context. That will replace the
	// view, dialog, url and version, so the context is in sync with the
	// navigation that took place.
	setContext(context, options.payload)

	// History state must be updated to preserve the expected, native browser behavior.
	// However, in some cases, we just want to swap the views without making an
	// actual navigation. Additionally, we don't want to actually push a new state
	// when navigating to the same URL.
	if (options.updateHistoryState !== false) {
		debug.router(`Target URL is ${context.url}, current window URL is ${window.location.href}.`, { shouldReplaceHistory })
		setHistoryState(context, {
			replace: shouldReplaceHistory,
		})
	}

	// Then, we swap the view.
	const viewComponent = await context.adapter.resolveComponent(context.view.name)
	debug.router(`Component [${context.view.name}] resolved to:`, viewComponent)
	await context.adapter.swapView({
		component: viewComponent,
		preserveState: shouldPreserveState,
	})

	// We then replace the dialog if a new one is given.
	if (context.dialog) {
		const dialogComponent = await context.adapter.resolveComponent(context.dialog.name)
		debug.router(`Dialog [${context.view.name}] resolved to:`, dialogComponent)
		await context.adapter.swapDialog({
			component: dialogComponent,
			preserveState: shouldPreserveState,
		})
	}

	if (!shouldPreserveScroll) {
		resetScrollPositions(context)
	} else {
		restoreScrollPositions(context)
	}

	context.events.emit('navigate', options)
}

/** Initializes the router by reading the context and registering events if necessary. */
async function initializeRouter(context: RouterContext): Promise<RouterContext> {
	if (isBackForwardVisit()) {
		handleBackForwardVisit(context)
	} else if (isExternalVisit()) {
		handleExternalVisit(context)
	} else {
		debug.router('Handling a normal visit.')

		// If we navigated to somewhere with a hash, we need to update the context
		// to add said hash because it was initialized without it.
		setContext(context, {
			url: makeUrl(context.url, { hash: window.location.hash }).toString(),
		})

		await navigate(context, {
			preserveState: true,
			replace: sameUrls(context.url, window.location.href),
		})
	}

	registerEventListeners(context)

	return context
}

/** Performs a local visit to the given component without a round-trip. */
async function performLocalComponentVisit(context: RouterContext, targetUrl: UrlResolvable, options: LocalVisitOptions) {
	const url = normalizeUrl(targetUrl)

	return await navigate(context, {
		...options,
		payload: {
			version: context.version,
			dialog: context.dialog,
			url,
			view: {
				name: options.component ?? context.view.name,
				properties: options.properties,
			},
		},
	})
}

/** Performs an external visit by changing the URL directly. */
function performLocalExternalVisit(url: UrlResolvable, data?: VisitOptions['data']) {
	document.location.href = makeUrl(url, {
		search: qs.stringify(data, {
			encodeValuesOnly: true,
			arrayFormat: 'brackets',
		}),
	}).toString()
}

type ConditionalNavigationOption = boolean | ((payload: VisitPayload) => boolean)

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
	/** Whether to preserve the current page component state. */
	preserveState?: ConditionalNavigationOption
	/** Whether to preserve the current URL. */
	preserveUrl?: ConditionalNavigationOption
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
	/** Predefined events for this visit. */
	events?: Partial<VisitEvents>
}

export interface VisitResponse {
	response?: AxiosResponse
	error?: {
		type: string
		actual: Error
	}
}

export type ResolveContext = () => RouterContext

export interface Router {
	/** Gets the context. */
	context: ResolveContext
	/** Aborts the currently pending visit, if any. */
	abort: () => Promise<void>
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
