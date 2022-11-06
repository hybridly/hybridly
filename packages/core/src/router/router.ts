import type { AxiosProgressEvent, AxiosResponse } from 'axios'
import { showResponseErrorModal, match, merge, when, debug, random, hasFiles, objectToFormData } from '@hybridly/utils'
import { ERROR_BAG_HEADER, EXCEPT_DATA_HEADER, EXTERNAL_NAVIGATION_HEADER, ONLY_DATA_HEADER, PARTIAL_COMPONENT_HEADER, HYBRIDLY_HEADER, VERSION_HEADER } from '../constants'
import { NotAHybridResponseError, NavigationCancelledError } from '../errors'
import type { InternalRouterContext, RouterContextOptions } from '../context'
import { getRouterContext, initializeContext, payloadFromContext, setContext } from '../context'
import { handleExternalNavigation, isExternalResponse, isExternalNavigation, performExternalNavigation, navigateToExternalUrl } from '../external'
import { resetScrollPositions, restoreScrollPositions, saveScrollPositions } from '../scroll'
import type { UrlResolvable } from '../url'
import { fillHash, makeUrl, normalizeUrl, sameUrls } from '../url'
import { runHooks } from '../plugins'
import { setHistoryState, isBackForwardNavigation, handleBackForwardNavigation, registerEventListeners, getHistoryState, getKeyFromHistory, remember } from './history'
import type { ConditionalNavigationOption, Errors, ComponentNavigationOptions, NavigationOptions, Router, HybridRequestOptions, HybridPayload, NavigationResponse } from './types'

/**
 * The hybridly router.
 * This is the core function that you can use to navigate in
 * your application. Make sure the routes you call return a
 * hybrid response, otherwise you need to call `external`.
 *
 * @example
 * router.get('/posts/edit', { post })
 */
export const router: Router = {
	abort: async() => getRouterContext().pendingNavigation?.controller.abort(),
	active: () => !!getRouterContext().pendingNavigation,
	// unstack: () => {},
	navigate: async(options) => await performHybridNavigation(options),
	reload: async(options) => await performHybridNavigation({ preserveScroll: true, preserveState: true, ...options }),
	get: async(url, options = {}) => await performHybridNavigation({ ...options, url, method: 'GET' }),
	post: async(url, options = {}) => await performHybridNavigation({ preserveState: true, ...options, url, method: 'POST' }),
	put: async(url, options = {}) => await performHybridNavigation({ preserveState: true, ...options, url, method: 'PUT' }),
	patch: async(url, options = {}) => await performHybridNavigation({ preserveState: true, ...options, url, method: 'PATCH' }),
	delete: async(url, options = {}) => await performHybridNavigation({ preserveState: true, ...options, url, method: 'DELETE' }),
	local: async(url, options) => await performLocalNavigation(url, options),
	external: (url, data = {}) => navigateToExternalUrl(url, data),
	history: {
		get: (key) => getKeyFromHistory(key),
		remember: (key, value) => remember(key, value),
	},
}

/** Creates the hybridly router. */
export async function createRouter(options: RouterContextOptions): Promise<InternalRouterContext> {
	await initializeContext(options)

	return await initializeRouter()
}

/** Performs every action necessary to make a hybrid navigation. */
export async function performHybridNavigation(options: HybridRequestOptions): Promise<NavigationResponse> {
	const navigationId = random()
	const context = getRouterContext()
	debug.router('Making a hybrid navigation:', { context, options, navigationId })

	try {
		// If applicable, converts the data to a `FormData` object.
		if ((hasFiles(options.data) || options.useFormData) && !(options.data instanceof FormData)) {
			options.data = objectToFormData(options.data)
			debug.router('Converted data to FormData.', options.data)

			// Automatically spoofs the method of the form, because it is not possible to
			// send a PUT, PATCH or DELETE request with files in them.
			if (options.method && ['PUT', 'PATCH', 'DELETE'].includes(options.method) && options.spoof !== false) {
				debug.router(`Automatically spoofing method ${options.method}.`)
				options.data.append('_method', options.method)
				options.method = 'POST'
			}
		}

		// Before anything else, we fire the "before" event to make sure
		// there was no user-specified handler returning "false".
		if (!await runHooks('before', options.hooks, options, context)) {
			debug.router('"before" event returned false, aborting the navigation.')
			throw new NavigationCancelledError('The navigation was cancelled by the "before" event.')
		}

		// Abort any active navigation.
		if (context.pendingNavigation) {
			debug.router('Aborting current navigation.', context.pendingNavigation)
			context.pendingNavigation?.controller.abort()
		}

		// Before making the navigation, we need to make sure the scroll positions are
		// saved, so we can restore them later.
		saveScrollPositions()

		// If the URL has transformation options, apply them before using the URL.
		if (options.url && options.transformUrl) {
			const transformUrl = typeof options.transformUrl === 'function'
				? options.transformUrl(makeUrl(options.url).toString())
				: options.transformUrl

			options.url = makeUrl(options.url, transformUrl)
		}

		// A navigation is being made, we need to add it to the context so it
		// can be reused later on.
		setContext({
			pendingNavigation: {
				id: navigationId,
				url: makeUrl(options.url ?? context.url),
				controller: new AbortController(),
				options,
			},
		})

		await runHooks('start', options.hooks, context)
		debug.router('Making request with axios.')

		const response = await context.axios.request({
			url: context.pendingNavigation!.url.toString(),
			method: options.method ?? 'GET',
			data: options.method === 'GET' ? {} : options.data,
			params: options.method === 'GET' ? options.data : {},
			signal: context.pendingNavigation!.controller.signal,
			headers: {
				...options.headers,
				...when(options.only !== undefined || options.except !== undefined, {
					[PARTIAL_COMPONENT_HEADER]: context.view.name,
					...when(options.only, { [ONLY_DATA_HEADER]: JSON.stringify(options.only) }, {}),
					...when(options.except, { [EXCEPT_DATA_HEADER]: JSON.stringify(options.except) }, {}),
				}, {}),
				...when(options.errorBag, { [ERROR_BAG_HEADER]: options.errorBag }, {}),
				...when(context.version, { [VERSION_HEADER]: context.version }, {}),
				[HYBRIDLY_HEADER]: true,
				'X-Requested-With': 'XMLHttpRequest',
				'Accept': 'text/html, application/xhtml+xml',
			},
			validateStatus: () => true,
			onUploadProgress: async(event: AxiosProgressEvent) => {
				await runHooks('progress', options.hooks, {
					event,
					percentage: Math.round(event.loaded / (event.total ?? 0) * 100),
				}, context)
			},
		})

		await runHooks('data', options.hooks, response, context)

		// An external response is a hybrid response that wants a full page
		// load to a requested URL. It may be the same URL, in which
		// case a full page refresh will be performed.
		if (isExternalResponse(response)) {
			debug.router('The response is explicitely external.')
			await performExternalNavigation({
				url: fillHash(context.pendingNavigation!.url, response.headers[EXTERNAL_NAVIGATION_HEADER]!),
				preserveScroll: options.preserveScroll === true,
			})

			return { response }
		}

		// An invalid response is a response that do not declare itself via
		// the protocole header.
		// In such cases, we want to throw to handler it later.
		if (!isHybridResponse(response)) {
			throw new NotAHybridResponseError(response)
		}

		// At this point, we know the response respects the hybridly protocol.
		debug.router('The response respects the Hybridly protocol.')
		const payload = response.data as HybridPayload

		// If the navigation was asking for specific properties, we ensure that the
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
				url: fillHash(context.pendingNavigation!.url, payload.url),
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
			await runHooks('error', options.hooks, errors, context)
			setContext({
				pendingNavigation: {
					...context.pendingNavigation as any,
					status: 'error',
				},
			})
		} else {
			await runHooks('success', options.hooks, payload, context)
			setContext({
				pendingNavigation: {
					...context.pendingNavigation as any,
					status: 'success',
				},
			})
		}

		return { response }
	//
	} catch (error: any) {
		await match(error.constructor.name, {
			NavigationCancelledError: async() => {
				debug.router('The request was cancelled through the "before" hook.', error)
				console.warn(error)
				await runHooks('abort', options.hooks, context)
			},
			AbortError: async() => {
				debug.router('The request was cancelled.', error)
				console.warn(error)
				await runHooks('abort', options.hooks, context)
			},
			NotAHybridResponseError: async() => {
				debug.router('The request was not hybridly.')
				console.error(error)
				await runHooks('invalid', options.hooks, error, context)
				showResponseErrorModal(error.response.data)
			},
			default: async() => {
				debug.router('An unknown error occured.', error)
				console.error(error)
				await runHooks('exception', options.hooks, error, context)
			},
		})

		await runHooks('fail', options.hooks, context)

		return {
			error: {
				type: error.constructor.name,
				actual: error,
			},
		}
	} finally {
		debug.router('Ending navigation.')
		await runHooks('after', options.hooks, context)

		// If the navigation is pending, it means another one has started,
		// and if we clean it up, it could lead to issues after the request is done.
		if (context.pendingNavigation?.id === navigationId) {
			setContext({ pendingNavigation: undefined })
		}
	}
}

/** Checks if the response contains a hybrid header. */
export function isHybridResponse(response: AxiosResponse): boolean {
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

	// If the navigation was asking to preserve the current state, we also need to
	// update the context's state from the history state.
	if (shouldPreserveState && getHistoryState() && options.payload.view.name === context.view.name) {
		debug.history('Setting the history from this entry into the context.')
		setContext({ state: getHistoryState() })
	}

	// If the navigation required the URL to be preserved, we skip its update
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

	await runHooks('navigated', {}, options, context)
}

/** Initializes the router by reading the context and registering events if necessary. */
async function initializeRouter(): Promise<InternalRouterContext> {
	const context = getRouterContext()

	if (isBackForwardNavigation()) {
		handleBackForwardNavigation()
	} else if (isExternalNavigation()) {
		handleExternalNavigation()
	} else {
		debug.router('Handling the initial page navigation.')

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

/** Performs a local navigation to the given component without a round-trip. */
async function performLocalNavigation(targetUrl: UrlResolvable, options: ComponentNavigationOptions) {
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
				properties: options.properties ?? {},
			},
		},
	})
}
