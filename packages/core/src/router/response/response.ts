import { debug, match, merge, showResponseErrorModal } from '@hybridly/utils'
import type { AxiosResponse } from 'axios'
import { runHooks } from '../../plugins'
import { getInternalRouterContext } from '../../context'
import { fillHash, sameHashes, sameUrls } from '../../url'
import { EXTERNAL_NAVIGATION_HEADER, HYBRIDLY_HEADER } from '../../constants'
import { handleDownloadResponse, isDownloadResponse } from '../../download'
import { NotAHybridResponseError } from '../../errors'
import { saveScrollPositions } from '../../scroll'
import type { Errors, HybridPayload, HybridRequestOptions, NavigationResponse } from '../types'
import { navigate } from '../view'
import { isExternalResponse, performExternalNavigation } from './external'
import type { HybridRequestResponse } from './response-stack'

// TODO: errors in a dedicated property

export async function handleHybridRequestResponse(requestResponse: HybridRequestResponse): Promise<NavigationResponse> {
	debug.router('Handling response', requestResponse)
	const context = getInternalRouterContext()
	const request = requestResponse.request
	const response = requestResponse.response
	const options = requestResponse.request.options

	try {
		// Before making the navigation, we need to make sure the scroll positions are
		// saved, so we can restore them later.
		saveScrollPositions()

		const result = await runHooks('data', options.hooks, request, response, context)

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
				url: fillHash(request.url, response.headers[EXTERNAL_NAVIGATION_HEADER]!),
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

		// If the navigation does not have a view, we keep the current one.
		// This should only happen when using dialogs.

		// TODO: granular property merging
		// TODO: merge properties-only responses

		// If the navigation was asking for specific properties, we ensure that the
		// new request object contains the properties of the current view context,
		// because the back-end sent back only the required properties.
		const properties = (() => {
			if (!payload.view || !isPartial(options)) {
				return undefined
			}

			if (!payload.view.component || payload.view.component === context.view.component) {
				return mergeProperties(context.view.properties, payload.view.properties, options.errorBag)
			}
		})()

		if (properties) {
			debug.router('Merged properties:', properties)
		}

		// If everything was according to the plan, we can make our navigation and
		// update the context. Underlying adapters get the updated data.
		await navigate({
			type: 'server',
			properties,
			payload: {
				...payload,
				url: fillHash(request.url, payload.url),
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
			await runHooks('error', options.hooks, request, errors, context)
		} else {
			await runHooks('success', options.hooks, payload, request, context)
		}

		return { response }
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

/** Checks if the response contains a hybrid header. */
export function isHybridResponse(response: AxiosResponse): boolean {
	return !!response?.headers[HYBRIDLY_HEADER]
}

function isPartial(options: HybridRequestOptions) {
	return options.only !== undefined || options.except !== undefined
}

function mergeProperties(original: Properties, incoming: Properties, errorBag?: string) {
	const mergedPayloadProperties = merge(original, incoming)

	// Overwrite errors with the errors coming in from the response instead of deeply merging them
	// which prevents errors from being removed when they are not present in the response.
	if (errorBag) {
		(mergedPayloadProperties.errors as any)[errorBag] = (incoming.errors as any)[errorBag] ?? {}
	} else {
		mergedPayloadProperties.errors = incoming.errors
	}

	return mergedPayloadProperties
}
