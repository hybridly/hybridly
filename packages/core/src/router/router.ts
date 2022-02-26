import axios, { AxiosResponse } from 'axios'
import defu from 'defu'
import { EXTERNAL_VISIT_HEADER, SLEIGHTFUL_HEADER } from '../constants'
import { NotASleightfulResponseError } from '../errors'
import type { VisitPayload, RequestPayload } from '../types'
import { debug, match, when } from '../utils'
import { createContext, payloadFromContext, RouterContext, RouterContextOptions, setContext } from './context'
import { handleExternalVisit, isExternalResponse, isExternalVisit, performExternalVisit } from './external'
import { setHistoryState, isBackForwardVisit, handleBackForwardVisit, registerEventListeners } from './history'
import { fillHash, makeUrl, UrlResolvable } from './url'

/** Creates the sleightful router. */
export async function createRouter(options: RouterContextOptions) {
	const context = await initializeRouter(createContext(options))

	// TODO events
	// TODO remembering state

	return {
		context,

		/** Makes a visit with the given options. */
		visit: async(options: VisitOptions) => {
			return await visit(context, options)
		},

		/** Reloads the current page. */
		reload: async(options: VisitOptions) => {
			return await visit(context, { preserveScroll: true, preserveState: true, ...options })
		},

		/** Makes a GET request to the given URL. */
		get: async(url: UrlResolvable, data: VisitOptions['data'] = {}, options: Omit<VisitOptions, 'method' | 'data' | 'url'> = {}) => {
			return await visit(context, { ...options, url, data, method: 'GET' })
		},

		/** Makes a POST request to the given URL. */
		post: async(url: UrlResolvable, data: VisitOptions['data'] = {}, options: Omit<VisitOptions, 'method' | 'data' | 'url'> = {}) => {
			return await visit(context, { preserveState: true, ...options, url, data, method: 'POST' })
		},

		/** Makes a PUT request to the given URL. */
		put: async(url: UrlResolvable, data: VisitOptions['data'] = {}, options: Omit<VisitOptions, 'method' | 'data' | 'url'> = {}) => {
			return await visit(context, { preserveState: true, ...options, url, data, method: 'PUT' })
		},

		/** Makes a PATCH request to the given URL. */
		patch: async(url: UrlResolvable, data: VisitOptions['data'] = {}, options: Omit<VisitOptions, 'method' | 'data' | 'url'> = {}) => {
			return await visit(context, { preserveState: true, ...options, url, data, method: 'PATCH' })
		},

		/** Makes a DELETE request to the given URL. */
		delete: async(url: UrlResolvable, data: VisitOptions['data'] = {}, options: Omit<VisitOptions, 'method' | 'data' | 'url'> = {}) => {
			return await visit(context, { preserveState: true, ...options, url, data, method: 'DELETE' })
		},
	}
}

/** Initializes the router by reading the context and registering events if necessary. */
async function initializeRouter(context: RouterContext): Promise<RouterContext> {
	if (isBackForwardVisit()) {
		handleBackForwardVisit(context)
	} else if (isExternalVisit()) {
		handleExternalVisit(context)
	} else {
		// If we navigated to somewhere with a hash, we need to update the context
		// to add said hash because it was initialized without it.
		setContext(context, {
			url: makeUrl(context.url, { hash: window.location.hash }).toString(),
		})

		await navigate(context, {
			preserveState: true,
		})
	}

	// TODO setup event handlers
	registerEventListeners(context)

	return context
}

export async function visit(context: RouterContext, options: VisitOptions) {
	debug.router('Making a visit:', { context, options })

	// TODO handle cancellation
	// TODO handle interruptions
	// TODO events
	// TODO form transformations
	// TODO preserveState from history

	try {
		const requestUrl = makeUrl(options.url ?? context.url)
		const response = await axios.request({
			url: requestUrl.toString(),
			method: options.method ?? 'GET',
			data: options.method === 'GET' ? {} : options.data,
			params: options.method === 'GET' ? options.data : {},
			headers: {
				...options.headers,
				...when(options.only?.length || options.except?.length, {
					'X-Sleightful-Partial-Component': context.view.name,
					...when(options.only, { ONLY_DATA_HEADER: JSON.stringify(options.only) }, {}),
					...when(options.except, { EXCEPT_DATA_HEADER: JSON.stringify(options.except) }, {}),
				}, {}),
				...when(options.errorBag, { 'X-Sleightful-Error-Bag': options.errorBag }, {}),
				...when(context.version, { 'X-Sleightful-Version': context.version }, {}),
				// 'X-Sleightful-Context': this.currentState.visit.context,
				'X-Requested-With': 'XMLHttpRequest',
				'SLEIGHTFUL_HEADER': true,
				'Accept': 'text/html, application/xhtml+xml',
			},
			onUploadProgress: (progress) => {
				if (options.data instanceof FormData) {
					progress.percentage = Math.round(progress.loaded / progress.total * 100)
					// this.events.emit('progress', progress)
				}
			},
		})

		debug.router('Response:', { response })

		// An invalid response is a response that do not declare itself via
		// the protocole header.
		// In such cases, we want to throw to handler it later.
		if (!isSleightfulResponse(response)) {
			debug.router('The response is not sleightful.')
			throw new NotASleightfulResponseError(response)
		}

		// An external response is a sleightful response that wants a full page
		// load to a requested URL. It may be the same URL, in which case a
		// full page refresh will be performed.
		if (isExternalResponse(response)) {
			debug.router('The response is explicitely external.')
			await performExternalVisit({
				url: fillHash(requestUrl, response.headers[EXTERNAL_VISIT_HEADER]),
				preserveScroll: options.preserveScroll === true,
			})
		}

		// At this point, we know the response is sleightful.
		debug.router('The response is sleightful.')
		const payload = response.data as VisitPayload

		// If the visit was asking for certain properties only, we ensure that the
		// new request object contains the properties of the current view context,
		// because the back-end sent back only the required properties.
		if (options.only && payload.view.name === context.view.name) {
			payload.view.properties = defu(payload.view.properties, context.view.properties)
		}

		// If the visit was asking to preserve the current state, we also need to
		// update the context's state from the history state.
		// if (options.preserveState && getHistoryState('state') && request.view.name === context.view.name) {
		// 	request.state = getHistoryState('state')
		// }

		// If everything was according to the plan, we can make our navigation and
		// update the context. Underlying adapters get the updated data.
		await navigate(context, {
			payload: {
				...payload,
				url: fillHash(requestUrl, payload.url),
			},
			preserveScroll: options.preserveScroll === true,
			preserveState: options.preserveState,
			replace: options.replace === true,
		})

		// If the new view's properties has errors, userland expects an event
		// with said errors to be emitted. However, errors can be scoped with
		// an error bag, and if the given error bag is missing, the event data
		// will be empty.
		if (context.view.properties.errors) {
			// TODO: emit error event, with scoped (options.errorBag) errors
		}

	//
	} catch (error: any) {
		match(error.constructor.name, {
			NotASleightfulResponseError: () => {
				console.error('Not a sleightful response', error)
			},
			default: () => {
				console.error('Unknown error', error)
			},
		})
	}
}

/** Checks if the response contains a sleightful header. */
export function isSleightfulResponse(response: AxiosResponse): boolean {
	return !!response?.headers[SLEIGHTFUL_HEADER]
}

/**
 * Makes an internal navigation that swaps the view and updates the context.
 * @internal This function is meant to be used internally.
 */
export async function navigate(context: RouterContext, options: NavigationOptions) {
	debug.router('Making an internal navigation:', { context, options })
	// const shouldReplace = options.replace || sameOrigin(context.url, window.location.href)

	// If no request was given, we use the current context instead.
	options.payload ??= payloadFromContext(context)

	// We merge the new request into the current context. That will replace the
	// view, dialog, url and version, so the context is in sync with the
	// navigation that took place.
	setContext(context, options.payload)

	// First, we swap the view.
	const viewComponent = await context.adapter.resolveComponent(options.payload.view.name)
	debug.router(`Component [${options.payload.view.name}] resolved to:`, viewComponent)
	await context.adapter.swapView({
		component: viewComponent,
		preserveState: options.preserveState,
	})

	// We then replace the dialog if a new one is given.
	if (options.payload.dialog) {
		const dialogComponent = await context.adapter.resolveComponent(options.payload.dialog.name)
		debug.router(`Dialog [${options.payload.view.name}] resolved to:`, dialogComponent)
		await context.adapter.swapDialog({
			component: dialogComponent,
			preserveState: options.preserveState,
		})
	}

	// History state must be updated to preserve the expected, native browser behavior.
	// However, in some cases, we just want to swap the views without making an
	// actual navigation.
	if (options.updateHistoryState !== false) {
		setHistoryState(context, { replace: options.replace })
	}

	if (options.preserveScroll) {
		// TODO
	}
}

export interface NavigationOptions {
	/** View to navigate to. */
	payload?: VisitPayload
	/**
	 * Whether to replace the current history state instead of adding
	 * one. This affects the browser's "back" and "forward" features.
	 */
	replace?: boolean
	/** Whether to preserve the current scrollbar position. */
	preserveScroll?: boolean
	/** Whether to preserve the current page component state. */
	preserveState?: boolean
	/**
	 * Defines whether the history state should be updated.
	 * @internal This is an advanced property meant to be used internally.
	 */
	updateHistoryState?: boolean
}

export interface VisitOptions extends Omit<NavigationOptions, 'request'> {
	/** The URL to visit. */
	url?: UrlResolvable
	/** HTTP verb to use for the request. */
	method?: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE'
	/** Body of the request. */
	data?: RequestPayload
	/** Which properties to update for this visit. Other properties will be ignored. */
	only?: string | string[]
	/** Which properties not to update for this visit. Other properties will be updated. */
	except?: string | string[]
	/** Specific headers to add to the request. */
	headers?: Record<string, string>
	/** The bag in which to put potential errors. */
	errorBag?: string
}
