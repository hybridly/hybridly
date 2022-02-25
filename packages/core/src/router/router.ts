import axios, { AxiosResponse } from 'axios'
import defu from 'defu'
import { EXTERNAL_VISIT_HEADER, SLEIGHTFUL_HEADER } from '../constants'
import { NotASleightfulResponseError } from '../errors'
import type { Properties, Property, RouterRequest, RequestData } from '../types'
import { match, when } from '../utils'
import { RouterContext, RouterContextOptions, setContext } from './context'
import { isExternalVisitResponse as isExternalResponse, performExternalVisit } from './external'
import { setHistoryState } from './history'
import { fillHash, makeUrl, sameOrigin } from './url'

export async function createRouter(options: RouterContextOptions) {}

export async function visit(context: RouterContext, options: VisitOptions) {
	// TODO handle cancellation
	// TODO handle interruptions
	// TODO events
	// TODO form transformations
	// TODO preserveState from history

	try {
		const requestUrl = makeUrl(options.url)
		const response = await axios.request({
			url: requestUrl.toString(),
			method: options.method ?? 'GET',
			data: options.method === 'GET' ? {} : options.data,
			params: options.method === 'GET' ? options.data : {},
			headers: {
				...options.headers,
				...when(options.only?.length || options.except?.length, {
					'X-Sleightful-Partial-Component': context.view.name,
					...when(options.only, { 'X-Sleightful-Only-Data': JSON.stringify(options.only) }, {}),
					...when(options.except, { 'X-Sleightful-Except-Data': JSON.stringify(options.except) }, {}),
				}, {}),
				...when(options.errorBag, { 'X-Sleightful-Error-Bag': options.errorBag }, {}),
				...when(context.version, { 'X-Sleightful-Version': context.version }, {}),
				// 'X-Sleightful-Context': this.currentState.visit.context,
				'X-Requested-With': 'XMLHttpRequest',
				'X-Sleightful': true,
				'Accept': 'text/html, application/xhtml+xml',
			},
			onUploadProgress: (progress) => {
				if (options.data instanceof FormData) {
					progress.percentage = Math.round(progress.loaded / progress.total * 100)
					// this.events.emit('progress', progress)
				}
			},
		})

		// An invalid response is a response that do not declare itself via
		// the protocole header.
		// In such cases, we want to throw to handler it later.
		if (!isSleightfulResponse(response)) {
			throw new NotASleightfulResponseError(response)
		}

		// An external response is a sleightful response that wants a full page
		// load to a requested URL. It may be the same URL, in which case a
		// full page refresh will be performed.
		if (isExternalResponse(response)) {
			await performExternalVisit({
				url: fillHash(requestUrl, response.headers[EXTERNAL_VISIT_HEADER]),
				preserveScroll: options.preserveScroll === true,
			})
		}

		// At this point, we know the response is sleightful.
		const routerRequest = response.data as RouterRequest

		// If the visit was asking for certain properties only, we ensure that the
		// new request object contains the properties of the current view context,
		// because the back-end sent back only the required properties.
		if (options.only && routerRequest.view.name === context.view.name) {
			routerRequest.view.properties = defu(routerRequest.view.properties, context.view.properties)
		}

		// If the visit was asking to preserve the current state, we also need to
		// update the context's state from the history state.
		// if (options.preserveState && getHistoryState('state') && request.view.name === context.view.name) {
		// 	request.state = getHistoryState('state')
		// }

		// If everything was according to the plan, we can make our navigation and
		// update the context. Underlying adapters get the updated data.
		await navigate(context, {
			request: {
				...routerRequest,
				url: fillHash(requestUrl, routerRequest.url),
			},
			preserveScroll: options.preserveScroll === true,
			preserveState: options.preserveState,
			replace: options.replace,
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
	const shouldReplace = options.replace || sameOrigin(context.url, window.location.href)

	// First, we swap the view.
	const viewComponent = await context.adapter.resolveComponent(options.request.view.name)
	await context.adapter.swapView({
		component: viewComponent,
		preserveState: options.preserveState,
	})

	// We then replace the dialog if a new one is given.
	if (options.request.dialog) {
		const dialogComponent = await context.adapter.resolveComponent(options.request.dialog.name)
		await context.adapter.swapDialog({
			component: dialogComponent,
			preserveState: options.preserveState,
		})
	}

	// We merge the new request into the current context. That will replace the
	// view, dialog, url and version, so the context is in sync with the
	// navigation that took place.
	setContext(context, options.request)

	// History state must be updated to preserve the expected, native browser behavior.
	// However, in some cases, we just want to swap the views without making an
	// actual navigation.
	if (options.updateHistoryState) {
		setHistoryState(context, { replace: shouldReplace })
	}

	if (options.preserveScroll) {
		// TODO
	}
}

export interface NavigationOptions {
	/** View to navigate to. */
	request: RouterRequest
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
	url: string
	/** HTTP verb to use for the request. */
	method?: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE'
	/** Body of the request. */
	data?: RequestData
	/** Which properties to update for this visit. Other properties will be ignored. */
	only?: Property[] | Properties
	/** Which properties not to update for this visit. Other properties will be updated. */
	except?: Property[] | Properties
	/** Specific headers to add to the request. */
	headers?: Record<string, string>
	/** The bag in which to put potential errors. */
	errorBag?: string
}
