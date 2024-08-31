import type { AxiosProgressEvent, AxiosResponse } from 'axios'
import { debug, hasFiles, match, mergeObject, objectToFormData, promiseWithResolvers, random, showResponseErrorModal, wrap } from '@hybridly/utils'
import { getInternalRouterContext, getRouterContext } from '../../context'
import { DIALOG_KEY_HEADER, DIALOG_REDIRECT_HEADER, ERROR_BAG_HEADER, EXCEPT_DATA_HEADER, HYBRIDLY_HEADER, ONLY_DATA_HEADER, PARTIAL_COMPONENT_HEADER, VERSION_HEADER } from '../../constants'
import { runHooks } from '../../plugins'
import { makeUrl } from '../../url'
import { NavigationCancelledError } from '../../errors'
import type { HybridRequestOptions, Method, NavigationResponse, PendingHybridRequest } from '../types'
import { enqueueRequest, getRequestQueue, interruptInFlight } from './request-stack'

export function createPendingHybridRequest(options: HybridRequestOptions): PendingHybridRequest {
	const context = getRouterContext()

	// Define the target URL by taking the URL given in the navigation
	// option or the current URL (stored in `context`), and apply
	// optional transforms specified in `options.transformUrl`.
	const url = makeUrl(options.url ?? context.url, options.transformUrl)

	const { promise, resolve } = promiseWithResolvers<NavigationResponse>()

	return {
		url,
		options,
		promise,
		resolve,
		id: random(),
		controller: new AbortController(),
		cancelled: false,
		completed: false,
		interrupted: false,
		view: context.view,
	} satisfies PendingHybridRequest
}

export async function sendHybridRequest(request: PendingHybridRequest): Promise<AxiosResponse> {
	const context = getInternalRouterContext()

	return await context.axios.request({
		url: request.url.toString(),
		method: request.options.method,
		data: request.options.method === 'GET' ? {} : request.options.data,
		params: request.options.method === 'GET' ? request.options.data : {},
		signal: request.controller?.signal,
		headers: {
			...request.options.headers,
			...(context.dialog ? { [DIALOG_KEY_HEADER]: context.dialog!.key } : {}),
			...(context.dialog ? { [DIALOG_REDIRECT_HEADER]: context.dialog!.redirectUrl ?? '' } : {}),
			...mergeObject(request.options.only !== undefined || request.options.except !== undefined, {
				[PARTIAL_COMPONENT_HEADER]: context.view.component,
				...mergeObject(request.options.only, { [ONLY_DATA_HEADER]: JSON.stringify(wrap(request.options.only)) }),
				...mergeObject(request.options.except, { [EXCEPT_DATA_HEADER]: JSON.stringify(wrap(request.options.except)) }),
			}),
			...mergeObject(request.options.errorBag, { [ERROR_BAG_HEADER]: request.options.errorBag }),
			...mergeObject(context.version, { [VERSION_HEADER]: context.version }),
			[HYBRIDLY_HEADER]: true,
			'X-Requested-With': 'XMLHttpRequest',
			'Accept': 'text/html, application/xhtml+xml',
		},
		responseType: 'arraybuffer',
		validateStatus: () => true,
		onUploadProgress: async (event: AxiosProgressEvent) => {
			await runHooks('progress', request.options.hooks, {
				event,
				percentage: Math.round(event.loaded / (event.total ?? 0) * 100),
			}, request, context)
		},
	})
}

export async function performHybridRequest(request: PendingHybridRequest): Promise<NavigationResponse> {
	// TODO: preloading
	const queue = getRequestQueue(request)

	interruptInFlight(queue)
	enqueueRequest(request)

	return request.promise
}

/** Performs every action necessary to make a hybrid navigation. */
export async function performHybridNavigation(options: HybridRequestOptions): Promise<NavigationResponse> {
	const context = getRouterContext()
	debug.router('Making a hybrid navigation:', { context, options })

	// Apply convenience changes to options.
	await transformOptions(options)

	const request = createPendingHybridRequest(options)

	try {
		// Before anything else, we fire the "before" event to make sure
		// there was no user-specified handler returning "false".
		if (!await runHooks('before', options.hooks, request, context)) {
			debug.router('"before" event returned false, aborting the navigation.')
			throw new NavigationCancelledError('The navigation was cancelled by the "before" event.')
		}

		await runHooks('start', options.hooks, request, context)
		debug.router('Making request with axios.')

		return await performHybridRequest(request)
	//
	} catch (error: any) {
		await match(error.constructor.name, {
			NavigationCancelledError: async () => {
				debug.router('The request was cancelled through the "before" hook.', error)
				await runHooks('abort', options.hooks, request, context)
			},
			AbortError: async () => {
				debug.router('The request was aborted.', error)
				await runHooks('abort', options.hooks, request, context)
			},
			NotAHybridResponseError: async () => {
				debug.router('The response was not hybrid.')
				console.error(error)
				await runHooks('invalid', options.hooks, request, error, context)
				if (context.responseErrorModals) {
					showResponseErrorModal(error.response.data)
				}
			},
			default: async () => {
				if (error?.name === 'CanceledError') {
					debug.router('The request was cancelled.', error)
					await runHooks('abort', options.hooks, request, context)
				} else {
					debug.router('An unknown error occured.', error)
					console.error(error)
					await runHooks('exception', options.hooks, error, request, context)
				}
			},
		})

		await runHooks('fail', options.hooks, request, context)

		return {
			error: {
				type: error.constructor.name,
				actual: error,
			},
		}
	} finally {
		debug.router('Ending navigation.')
		await runHooks('after', options.hooks, request, context)
	}
}

/**
 * Transform the options object with convenience changes.
 */
export async function transformOptions(options: HybridRequestOptions) {
	const context = getRouterContext()

	// Sets the method if not specifically defined.
	if (!options.method) {
		debug.router('Setting method to GET because none was provided.')
		options.method = 'GET'
	}

	// Force uppercase method because we accept lowercase methods,
	// *angry look at Hassan*
	options.method = options.method.toUpperCase() as Method

	// By default, don't show progress when a request is asynchronous.
	if (options.async === true && options.progress === undefined) {
		options.progress = false
	}

	if (options.async === true && options.replace === undefined) {
		options.replace = true
	}

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
		} else if (typeof options.data === 'undefined') {
			options.data = { _method: options.method }
		}	else if (options.data instanceof Object && Object.keys(options.data).length >= 0) {
			Object.assign(options.data!, { _method: options.method })
		} else {
			debug.router('Could not spoof method because body type is not supported.', options.data)
		}

		options.method = 'POST'
	}

	return options
}
