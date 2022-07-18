import axios, { AxiosResponse } from 'axios'
import qs from 'qs'
import { showResponseErrorModal, match, merge, when, debug, random } from '@hybridly/utils'
import { ERROR_BAG_HEADER, EXCEPT_DATA_HEADER, EXTERNAL_VISIT_HEADER, ONLY_DATA_HEADER, PARTIAL_COMPONENT_HEADER, HYBRIDLY_HEADER, VERSION_HEADER } from '../constants'
import { NotAHybridlyResponseError, VisitCancelledError } from '../errors'
import { getRouterContext, initializeContext, payloadFromContext, InternalRouterContext, RouterContextOptions, setContext } from '../context'
import { triggerEvent } from '../events'
import { handleExternalVisit, isExternalResponse, isExternalVisit, performExternalVisit } from '../external'
import { resetScrollPositions, restoreScrollPositions, saveScrollPositions } from '../scroll'
import { fillHash, makeUrl, normalizeUrl, sameUrls, UrlResolvable } from '../url'
import { setHistoryState, isBackForwardVisit, handleBackForwardVisit, registerEventListeners, getHistoryState, getKeyFromHistory, remember } from './history'
import type { ConditionalNavigationOption, Errors, LocalVisitOptions, NavigationOptions, Router, VisitOptions, VisitPayload, VisitResponse } from './types'

/**
 * The hybridly router.
 * This is the core function that you can use to navigate in
 * your application. Make sure the routes you call return a
 * hybridly response, otherwise you need to call `external`.
 *
 * @example
 * router.get('/posts/edit', { post })
 */
export const router: Router = {
	abort: async() => getRouterContext().activeVisit?.controller.abort(),
	active: () => !!getRouterContext().activeVisit,
	// unstack: () => {},
	visit: async(options) => await visit(options),
	reload: async(options) => await visit({ preserveScroll: true, preserveState: true, ...options }),
	get: async(url, options = {}) => await visit({ ...options, url, method: 'GET' }),
	post: async(url, options = {}) => await visit({ preserveState: true, ...options, url, method: 'POST' }),
	put: async(url, options = {}) => await visit({ preserveState: true, ...options, url, method: 'PUT' }),
	patch: async(url, options = {}) => await visit({ preserveState: true, ...options, url, method: 'PATCH' }),
	delete: async(url, options = {}) => await visit({ preserveState: true, ...options, url, method: 'DELETE' }),
	local: async(url, options) => await performLocalComponentVisit(url, options),
	external: (url, data = {}) => performLocalExternalVisit(url, data),
	history: {
		get: (key) => getKeyFromHistory(key),
		remember: (key, value) => remember(key, value),
	},
}

/** Creates the hybridly router. */
export async function createRouter(options: RouterContextOptions): Promise<InternalRouterContext> {
	initializeContext(options)

	return await initializeRouter()
}

/** Performs every action necessary to make a hybridly visit. */
export async function visit(options: VisitOptions): Promise<VisitResponse> {
	const visitId = random()
	const context = getRouterContext()
	debug.router('Making a visit:', { context, options, visitId })

	try {
		// Abort any active visit.
		if (context.activeVisit) {
			debug.router('Aborting current visit.', context.activeVisit)
			context.activeVisit?.controller.abort()
		}

		// Before anything else, we fire the "before" event to make sure
		// there was no user-specified handler returning "false".
		if (!(await triggerEvent('before', options, options.events?.before))) {
			debug.router('"before" event returned false, aborting the visit.')
			throw new VisitCancelledError('The visit was cancelled by the "before" event.')
		}

		// Before making the visit, we need to make sure the scroll positions are
		// saved, so we can restore them later.
		saveScrollPositions()

		// If the URL has transformation options, apply them before using the URL.
		if (options.url && options.transformUrl) {
			options.url = makeUrl(options.url, options.transformUrl)
		}

		// A visit is being made, we need to add it to the context so it
		// can be reused later on.
		setContext({
			activeVisit: {
				id: visitId,
				url: makeUrl(options.url ?? context.url),
				controller: new AbortController(),
				options,
			},
		})

		triggerEvent('start', context, options.events?.start)
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
				triggerEvent('progress', {
					event,
					percentage: Math.round(event.loaded / event.total * 100),
				}, options.events?.progress)
			},
		})

		triggerEvent('data', response, options.events?.data)

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

		// At this point, we know the response respects the hybridly protocol.
		debug.router('The response respects the hybridly protocol.')
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
		await navigate({
			payload: {
				...payload,
				url: fillHash(context.activeVisit!.url, payload.url),
			},
			preserveScroll: options.preserveScroll === true,
			preserveState: options.preserveState,
			preserveUrl: options.preserveUrl,
			replace: options.replace === true || sameUrls(payload.url, window.location.href) || options.preserveUrl,
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
			triggerEvent('error', errors, options.events?.error)
			setContext({
				activeVisit: {
					...context.activeVisit as any,
					status: 'error',
				},
			})
		} else {
			triggerEvent('success', payload, options.events?.success)
			setContext({
				activeVisit: {
					...context.activeVisit as any,
					status: 'success',
				},
			})
		}

		return { response }
	//
	} catch (error: any) {
		match(error.constructor.name, {
			AbortError: () => {
				debug.router('The request was cancelled.', error)
				triggerEvent('abort', context, options.events?.abort)
			},
			NotAHybridlyResponseError: () => {
				debug.router('The request was not hybridly.')
				triggerEvent('invalid', error, options.events?.invalid)
				showResponseErrorModal(error.response.data)
			},
			default: () => {
				debug.router('An unknown error occured.', error)
				triggerEvent('exception', error, options.events?.exception)
			},
		})

		console.error(error)
		triggerEvent('fail', context, options.events?.fail)

		return {
			error: {
				type: error.constructor.name,
				actual: error,
			},
		}
	} finally {
		debug.router('Ending visit.')
		triggerEvent('after', context, options.events?.after)

		// If the visit is pending, it means another one has started,
		// and if we clean it up, it could lead to issues after the request is done.
		if (context.activeVisit?.id === visitId) {
			setContext({ activeVisit: undefined })
		}
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
export async function navigate(options: NavigationOptions) {
	const context = getRouterContext()
	debug.router('Making an internal navigation:', { context, options })

	// If no request was given, we use the current context instead.
	options.payload ??= payloadFromContext()

	const evaluateConditionalOption = (option?: ConditionalNavigationOption) => typeof option === 'function'
		? option(options.payload!)
		: option

	const shouldPreserveState = evaluateConditionalOption(options.preserveState)
	const shouldPreserveScroll = evaluateConditionalOption(options.preserveScroll)
	const shouldReplaceHistory = evaluateConditionalOption(options.replace)
	const shouldReplaceUrl = evaluateConditionalOption(options.preserveUrl)

	// If the visit was asking to preserve the current state, we also need to
	// update the context's state from the history state.
	if (shouldPreserveState && getHistoryState() && options.payload.view.name === context.view.name) {
		setContext({ state: getHistoryState() })
	}

	// If the visit required the URL to be preserved, we skip its update
	// by replacing the payload URL with the current context URL.
	if (shouldReplaceUrl) {
		debug.router(`Preserving the current URL (${context.url}) instead of navigating to ${options.payload.url}`)
		options.payload!.url = context.url
	}

	// We merge the new request into the current context. That will replace
	// view, dialog, url and version, so the context is in sync with the
	// navigation that took place. We do not propagate the context
	// changes so adapters don't update props before the view.
	// We also reset the state so the state from one page
	// is not merged with the state from another one.
	setContext({
		...options.payload,
		state: {},
	}, { propagate: false })

	// History state must be updated to preserve the expected, native browser behavior.
	// However, in some cases, we just want to swap the views without making an
	// actual navigation. Additionally, we don't want to actually push a new state
	// when navigating to the same URL.
	if (options.updateHistoryState !== false) {
		debug.router(`Target URL is ${context.url}, current window URL is ${window.location.href}.`, { shouldReplaceHistory })
		setHistoryState({ replace: shouldReplaceHistory })
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

	// Triggers a context propagation, needed after the views were swapped,
	// so their properties can properly be updated accordingly.
	setContext()

	if (!shouldPreserveScroll) {
		resetScrollPositions()
	} else {
		restoreScrollPositions()
	}

	triggerEvent('navigate', options)
}

/** Initializes the router by reading the context and registering events if necessary. */
async function initializeRouter(): Promise<InternalRouterContext> {
	const context = getRouterContext()

	if (isBackForwardVisit()) {
		handleBackForwardVisit()
	} else if (isExternalVisit()) {
		handleExternalVisit()
	} else {
		debug.router('Handling the initial page visit.')

		// If we navigated to somewhere with a hash, we need to update the context
		// to add said hash because it was initialized without it.
		setContext({
			url: makeUrl(context.url, { hash: window.location.hash }).toString(),
		})

		await navigate({
			preserveState: true,
			replace: sameUrls(context.url, window.location.href),
		})
	}

	registerEventListeners()

	return context
}

/** Performs a local visit to the given component without a round-trip. */
async function performLocalComponentVisit(targetUrl: UrlResolvable, options: LocalVisitOptions) {
	const context = getRouterContext()
	const url = normalizeUrl(targetUrl)

	return await navigate({
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
