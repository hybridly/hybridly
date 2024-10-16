import { debug, getByPath, merge, setByPath, wrap } from '@hybridly/utils'
import type { AxiosResponse } from 'axios'
import { runHooks } from '../../plugins'
import { getInternalRouterContext } from '../../context'
import { fillHash, sameHashes, sameUrls } from '../../url'
import { EXTERNAL_NAVIGATION_HEADER, HYBRIDLY_HEADER } from '../../constants'
import { handleDownloadResponse, isDownloadResponse } from '../../download'
import { NotAHybridResponseError } from '../../errors'
import { saveScrollPositions } from '../../scroll'
import type { Errors, HybridPayload, HybridRequestOptions, NavigationResponse, View } from '../types'
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

	// We only want to make a page navigation if the request was synchronous
	// or if we didn't navigate during the request and the response.
	if (!options.async || (context.view.component === request.view.component)) {
		const properties = (() => {
			if (!payload.view && !isPartial(options)) {
				return undefined
			}

			if (!payload.view.component || (payload.view.component === context.view.component)) {
				return resolveProperties(context.view.properties, payload.view, options.errorBag)
			}
		})()

		if (properties) {
			debug.router('Merged properties:', properties)
		}

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
	} else {
		debug.router('Discarding navigation from an async request initiated on a previous page.')
	}

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
}

/** Checks if the response contains a hybrid header. */
export function isHybridResponse(response: AxiosResponse): boolean {
	return !!response?.headers[HYBRIDLY_HEADER]
}

function isPartial(options: HybridRequestOptions) {
	return options.only !== undefined || options.except !== undefined
}

function resolveProperties(original: Properties, payload: View, errorBag?: string) {
	const mergedPayloadProperties = merge(original, payload.properties)

	// TODO: errors in their own property
	// Overwrite errors with the errors coming in from the response instead of deeply merging them
	// which prevents errors from being removed when they are not present in the response.
	if (errorBag) {
		(mergedPayloadProperties.errors as any)[errorBag] = (payload.properties.errors as any)[errorBag] ?? {}
	} else {
		mergedPayloadProperties.errors = payload.properties.errors
	}

	(payload.mergeable ?? []).forEach(([mergeableProperty, unique]) => {
		const originalValue = getByPath(original, mergeableProperty) as Properties
		const newValue = getByPath(payload.properties, mergeableProperty) as Properties

		if (Array.isArray(originalValue)) {
			const array = [
				...originalValue,
				...wrap(newValue),
			]

			setByPath(mergedPayloadProperties, mergeableProperty, unique ? [...new Set(array)] : array)
			return
		}

		if (originalValue instanceof Object) {
			setByPath(mergedPayloadProperties, mergeableProperty, merge(originalValue, newValue, { overwriteArray: false }))
			return
		}

		setByPath(mergedPayloadProperties, mergeableProperty, newValue)
	})

	return mergedPayloadProperties
}
