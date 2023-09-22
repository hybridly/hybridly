import type { AxiosProgressEvent, AxiosResponse } from 'axios'
import { showResponseErrorModal, match, merge, when, debug, random, hasFiles, objectToFormData } from '@hybridly/utils'
import { ERROR_BAG_HEADER, EXCEPT_DATA_HEADER, EXTERNAL_NAVIGATION_HEADER, ONLY_DATA_HEADER, PARTIAL_COMPONENT_HEADER, HYBRIDLY_HEADER, VERSION_HEADER, DIALOG_KEY_HEADER, DIALOG_REDIRECT_HEADER } from '../constants'
import { NotAHybridResponseError, NavigationCancelledError } from '../errors'
import type { InternalRouterContext, RouterContextOptions } from '../context'
import { getInternalRouterContext, getRouterContext, initializeContext, payloadFromContext, setContext } from '../context'
import { handleExternalNavigation, isExternalResponse, isExternalNavigation, performExternalNavigation, navigateToExternalUrl } from '../external'
import { resetScrollPositions, restoreScrollPositions, saveScrollPositions } from '../scroll'
import type { UrlResolvable } from '../url'
import { sameHashes, fillHash, makeUrl, normalizeUrl, sameUrls } from '../url'
import { runHooks } from '../plugins'
import { generateRouteFromName, getRouteDefinition } from '../routing/route'
import { closeDialog } from '../dialog'
import { currentRouteMatches, getCurrentRouteName } from '../routing/current'
import { handleDownloadResponse, isDownloadResponse } from '../download'
import { setHistoryState, isBackForwardNavigation, handleBackForwardNavigation, registerEventListeners, getHistoryMemo, remember } from './history'
import type { ConditionalNavigationOption, Errors, ComponentNavigationOptions, Router, HybridRequestOptions, HybridPayload, NavigationResponse, Method, InternalNavigationOptions } from './types'
import { discardPreloadedRequest, getPreloadedRequest, performPreloadRequest } from './preload'

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
	navigate: async(options) => await performHybridNavigation(options),
	reload: async(options) => await performHybridNavigation({ preserveScroll: true, preserveState: true, replace: true, ...options }),
	get: async(url, options = {}) => await performHybridNavigation({ ...options, url, method: 'GET' }),
	post: async(url, options = {}) => await performHybridNavigation({ preserveState: true, ...options, url, method: 'POST' }),
	put: async(url, options = {}) => await performHybridNavigation({ preserveState: true, ...options, url, method: 'PUT' }),
	patch: async(url, options = {}) => await performHybridNavigation({ preserveState: true, ...options, url, method: 'PATCH' }),
	delete: async(url, options = {}) => await performHybridNavigation({ preserveState: true, ...options, url, method: 'DELETE' }),
	local: async(url, options = {}) => await performLocalNavigation(url, options),
	preload: async(url, options = {}) => await performPreloadRequest({ ...options, url, method: 'GET' }),
	external: (url, data = {}) => navigateToExternalUrl(url, data),
	to: async(name, parameters, options) => {
		const url = generateRouteFromName(name, parameters)
		const method = getRouteDefinition(name).method.at(0)
		return await performHybridNavigation({ url, ...options, method })
	},
	matches: (name, parameters) => currentRouteMatches(name, parameters),
	current: () => getCurrentRouteName(),
	dialog: {
		close: (options) => closeDialog(options),
	},
	history: {
		get: (key) => getHistoryMemo(key),
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
		// Sets the method if not specifically defined.
		if (!options.method) {
			debug.router('Setting method to GET because none was provided.')
			options.method = 'GET'
		}

		// Force uppercase method because we accept lowercase methods,
		// *angry look at Hassan*
		options.method = options.method.toUpperCase() as Method

		// If applicable, converts the data to a `FormData` object.
		if ((hasFiles(options.data) || options.useFormData) && !(options.data instanceof FormData)) {
			options.data = objectToFormData(options.data)
			debug.router('Converted data to FormData.', options.data)
		}

		// Converts data to query parameters if the method is GET
		// and some non-FormData data is provided.
		if (!(options.data instanceof FormData) && options.method === 'GET' && Object.keys(options.data ?? {}).length) {
			debug.router('Transforming data to query parameters.', options.data)
			options.url = makeUrl(options.url ?? context.url, {
				query: options.data,
			})
			options.data = {}
		}

		// Automatically spoofs PUT, PATCH and DELETE requests.
		if (['PUT', 'PATCH', 'DELETE'].includes(options.method) && options.spoof !== false) {
			debug.router(`Automatically spoofing method ${options.method}.`)

			if (options.data instanceof FormData) {
				options.data.append('_method', options.method)
			} else if (Object.keys(options.data ?? {}).length) {
				Object.assign(options.data!, { _method: options.method })
			} else if (typeof options.data === 'undefined') {
				options.data = { _method: options.method }
			} else {
				debug.router('Could not spoof method because body type is not supported.', options.data)
			}

			options.method = 'POST'
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
			context.pendingNavigation?.controller?.abort()
		}

		// Before making the navigation, we need to make sure the scroll positions are
		// saved, so we can restore them later.
		saveScrollPositions()

		// If the URL has transformation options, apply them before using the URL.
		if (options.url && options.transformUrl) {
			options.url = makeUrl(options.url, options.transformUrl)
		}

		const targetUrl = makeUrl(options.url ?? context.url)
		const abortController = new AbortController()

		// A navigation is being made, we need to add it to the context so it
		// can be reused later on.
		setContext({
			pendingNavigation: {
				id: navigationId,
				url: targetUrl,
				controller: abortController,
				status: 'pending',
				options,
			},
		})

		await runHooks('start', options.hooks, context)
		debug.router('Making request with axios.')
		const response = await performHybridRequest(targetUrl, options, abortController)
		const result = await runHooks('data', options.hooks, response, context)

		// If one of the `data` hook decided to cancel the,
		// response we stop processing it and return early.
		if (result === false) {
			return { response }
		}

		// An external response is a hybrid response that wants a full page
		// load to a requested URL. It may be the same URL, in which
		// case a full page refresh will be performed.
		if (isExternalResponse(response)) {
			debug.router('The response is explicitely external.')
			await performExternalNavigation({
				url: fillHash(targetUrl, response.headers[EXTERNAL_NAVIGATION_HEADER]!),
				preserveScroll: options.preserveScroll === true,
			})

			return { response }
		}

		if (isDownloadResponse(response)) {
			debug.router('The response returns a file to download.')
			await handleDownloadResponse(response)

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
		if ((options.only?.length ?? options.except?.length) && payload.view.component === context.view.component) {
			debug.router(`Merging ${options.only ? '"only"' : '"except"'} properties.`, payload.view.properties)
			payload.view.properties = merge(context.view.properties, payload.view.properties)
			debug.router('Merged properties:', payload.view.properties)
		}

		// If everything was according to the plan, we can make our navigation and
		// update the context. Underlying adapters get the updated data.
		await navigate({
			type: 'server',
			payload: {
				...payload,
				url: fillHash(targetUrl, payload.url),
			},
			preserveScroll: options.preserveScroll,
			preserveState: options.preserveState,
			preserveUrl: options.preserveUrl,
			replace: options.replace === true || options.preserveUrl || (sameUrls(payload.url, window.location.href) && !sameHashes(payload.url, window.location.href)),
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
			setContext({
				pendingNavigation: {
					...context.pendingNavigation!,
					status: 'error',
				},
			})

			await runHooks('error', options.hooks, errors, context)
		} else {
			setContext({
				pendingNavigation: {
					...context.pendingNavigation!,
					status: 'success',
				},
			})

			await runHooks('success', options.hooks, payload, context)
		}

		return { response }
	//
	} catch (error: any) {
		await match(error.constructor.name, {
			NavigationCancelledError: async() => {
				debug.router('The request was cancelled through the "before" hook.', error)
				await runHooks('abort', options.hooks, context)
			},
			AbortError: async() => {
				debug.router('The request was aborted.', error)
				await runHooks('abort', options.hooks, context)
			},
			NotAHybridResponseError: async() => {
				debug.router('The request was not hybridly.')
				console.error(error)
				await runHooks('invalid', options.hooks, error, context)
				if (context.responseErrorModals) {
					showResponseErrorModal(error.response.data)
				}
			},
			default: async() => {
				if (error?.name === 'CanceledError') {
					debug.router('The request was cancelled.', error)
					await runHooks('abort', options.hooks, context)
				} else {
					debug.router('An unknown error occured.', error)
					console.error(error)
					await runHooks('exception', options.hooks, error, context)
				}
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
export async function navigate(options: InternalNavigationOptions) {
	const context = getRouterContext()

	// Since there is no other way to know prior to the navigation actually being made,
	// we mutate `options` here to add whether there is a dialog or not.
	options.hasDialog ??= !!options.payload?.dialog

	debug.router('Making an internal navigation:', { context, options })
	await runHooks('navigating', {}, options, context)

	// If no request was given, we use the current context instead.
	options.payload ??= payloadFromContext()

	function evaluateConditionalOption<T extends boolean | string>(option?: ConditionalNavigationOption<T>) {
		return typeof option === 'function'
			? option(options)
			: option
	}

	const shouldPreserveState = evaluateConditionalOption(options.preserveState)
	const shouldPreserveScroll = evaluateConditionalOption(options.preserveScroll)
	const shouldReplaceHistory = evaluateConditionalOption(options.replace)
	const shouldReplaceUrl = evaluateConditionalOption(options.preserveUrl)
	const shouldPreserveView = !options.payload.view.component

	// If the navigation was asking to preserve the current state, we also need to
	// update the context's state from the history state.
	if (shouldPreserveState && getHistoryMemo() && options.payload.view.component === context.view.component) {
		debug.history('Setting the memo from this history entry into the current context.')
		setContext({ memo: getHistoryMemo() })
	}

	// If the navigation required the URL to be preserved, we skip its update
	// by replacing the payload URL with the current context URL.
	if (shouldReplaceUrl) {
		debug.router(`Preserving the current URL (${context.url}) instead of navigating to ${options.payload.url}`)
		options.payload!.url = context.url
	}

	// If we didn't receive a component name,
	// we don't swap views and we preserve the url.
	const payload = shouldPreserveView
		? {
			view: {
				component: context.view.component,
				properties: merge(context.view.properties, options.payload.view.properties),
				deferred: context.view.deferred,
			},
			url: context.url,
			version: options.payload.version,
			dialog: context.dialog,
		} satisfies HybridPayload
		: options.payload

	// We merge the new request into the current context. That will replace
	// view, dialog, url and version, so the context is in sync with the
	// navigation that took place.
	setContext({ ...payload, memo: {} })

	// History state must be updated to preserve the expected, native browser behavior.
	// However, in some cases, we just want to swap the views without making an
	// actual navigation. Additionally, we don't want to actually push a new state
	// when navigating to the same URL.
	if (options.updateHistoryState !== false) {
		debug.router(`Target URL is ${context.url}, current window URL is ${window.location.href}.`, { shouldReplaceHistory })
		setHistoryState({ replace: shouldReplaceHistory })
	}

	// If there are deferred properties, we handle them
	// by making a partial-reload after the view component has mounted
	if (context.view.deferred?.length) {
		debug.router('Request has deferred properties, queueing a partial reload:', context.view.deferred)
		context.adapter.executeOnMounted(async() => {
			await performHybridNavigation({
				preserveScroll: true,
				preserveState: true,
				replace: true,
				only: context.view.deferred,
			})
		})
	}

	// Then, we swap the view.
	const viewComponent = !shouldPreserveView
		? await context.adapter.resolveComponent(context.view.component!)
		: undefined

	if (viewComponent) {
		debug.router(`Component [${context.view.component}] resolved to:`, viewComponent)
	}

	await context.adapter.onViewSwap({
		component: viewComponent,
		dialog: context.dialog,
		properties: options.payload?.view?.properties,
		preserveState: shouldPreserveState,
		onMounted: (hookOptions) => runHooks('mounted', {}, { ...options, ...hookOptions }, context),
	})

	if (options.type === 'back-forward') {
		restoreScrollPositions()
	} else if (!shouldPreserveScroll) {
		resetScrollPositions()
	}

	await runHooks('navigated', {}, options, context)
}

export async function performHybridRequest(targetUrl: URL, options: HybridRequestOptions, abortController?: AbortController): Promise<AxiosResponse> {
	const context = getInternalRouterContext()
	const preloaded = getPreloadedRequest(targetUrl)

	if (preloaded) {
		debug.router(`Found a pre-loaded request for [${targetUrl}]`)
		discardPreloadedRequest(targetUrl)

		return preloaded
	}

	return await context.axios.request({
		url: targetUrl.toString(),
		method: options.method,
		data: options.method === 'GET' ? {} : options.data,
		params: options.method === 'GET' ? options.data : {},
		signal: abortController?.signal,
		headers: {
			...options.headers,
			...(context.dialog ? { [DIALOG_KEY_HEADER]: context.dialog!.key } : {}),
			...(context.dialog ? { [DIALOG_REDIRECT_HEADER]: context.dialog!.redirectUrl ?? '' } : {}),
			...when(options.only !== undefined || options.except !== undefined, {
				[PARTIAL_COMPONENT_HEADER]: context.view.component,
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
}

/** Initializes the router by reading the context and registering events if necessary. */
async function initializeRouter(): Promise<InternalRouterContext> {
	const context = getRouterContext()

	if (isBackForwardNavigation()) {
		handleBackForwardNavigation()
	} else if (isExternalNavigation()) {
		handleExternalNavigation()
	} else {
		debug.router('Handling the initial navigation.')

		// If we navigated to somewhere with a hash, we need to update the context
		// to add said hash because it was initialized without it.
		setContext({
			url: makeUrl(context.url, { hash: window.location.hash }).toString(),
		})

		await navigate({
			type: 'initial',
			preserveState: true,
			replace: sameUrls(context.url, window.location.href),
		})
	}

	registerEventListeners()

	await runHooks('ready', {}, context)

	return context
}

/** Performs a local navigation to the given component without a round-trip. */
export async function performLocalNavigation(targetUrl: UrlResolvable, options?: ComponentNavigationOptions) {
	const context = getRouterContext()
	const url = normalizeUrl(targetUrl)

	return await navigate({
		...options,
		type: 'local',
		payload: {
			version: context.version,
			dialog: options?.dialog === false ? undefined : (options?.dialog ?? context.dialog),
			url,
			view: {
				component: options?.component ?? context.view.component,
				properties: options?.properties ?? {},
				deferred: [],
			},
		},
	})
}
