import { debug, merge, showResponseErrorModal } from '@hybridly/utils'
import { get, set, uniqBy } from 'es-toolkit/compat'
import { EXTERNAL_NAVIGATION_HEADER, HYBRIDLY_HEADER } from '../../constants'
import { getInternalRouterContext } from '../../context'
import { handleDownloadResponse, isDownloadResponse } from '../../download'
import { InvalidResponseError } from '../../errors'
import type { HttpResponse } from '../../http'
import { runHooks } from '../../plugins'
import { saveScrollPositions } from '../../scroll'
import { fillHash, sameHashes, sameUrls } from '../../url'
import { evaluateConditionalOption } from '../../utils'
import type { Errors, HybridPayload, HybridRequestOptions, NavigationResponse, Properties, Validation, View } from '../types'
import { navigate } from '../view'
import { isExternalResponse, performExternalNavigation } from './external'
import type { HybridRequestResponse } from './response-manager'

export async function handleHybridRequestResponse({ request, response }: HybridRequestResponse): Promise<NavigationResponse> {
	debug.router('Handling response', response)
	const context = getInternalRouterContext()
	const options = request.options

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
			url: fillHash(request.url, response.headers.get(EXTERNAL_NAVIGATION_HEADER)!),
			preserveScroll: options.preserveScroll === true,
			target: 'current',
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
		debug.router('The response was not hybrid.')
		console.warn('Hybridly received an invalid response.', response)

		await runHooks('fail', request.options.hooks, new InvalidResponseError(), request, context)
		const prevented = !await runHooks('invalid', request.options.hooks, request, response!, context)

		if (context.responseErrorModals && !prevented) {
			showResponseErrorModal(
				typeof response!.data === 'string'
					? response.data
					: JSON.stringify(response!.data, null, 2),
			)
		}

		return { response }
	}

	// At this point, we know the response respects the hybridly protocol.
	debug.router('The response respects the Hybridly protocol.')
	const payload = response.data as HybridPayload

	const mergedValidation = mergeValidation(context.validation, payload.validation, options.errorBag)
	const incomingErrors = resolveErrors(payload.validation, options.errorBag)

	// We only want to make a page navigation if the request was synchronous
	// or if we didn't navigate during the request and the response.
	if (options.mode !== 'async' || (context.view.component === request.view.component)) {
		const properties = (() => {
			if (!payload.view && !isPartial(options)) {
				return undefined
			}

			if (!payload.view.component || (payload.view.component === context.view.component)) {
				return resolveProperties(context.view.properties, payload.view, {
					mergeWithOriginal: evaluateConditionalOption(options, options.preserveState) !== false,
				})
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
				validation: mergedValidation,
				url: fillHash(request.url, payload.url),
			},
			preserveScroll: options.preserveScroll,
			preserveState: options.preserveState,
			preserveUrl: options.preserveUrl,
			replace: options.replace === true || options.preserveUrl || (sameUrls(payload.url, window.location.href) && !sameHashes(payload.url, window.location.href)),
			viewTransition: options.viewTransition,
		})
	} else {
		debug.router('Discarding navigation from an asynchronous request initiated on a previous page.')
	}

	if (Object.keys(incomingErrors).length > 0) {
		debug.router('The request returned validation errors.', incomingErrors)

		const errors = resolveErrors(context.validation, options.errorBag)
		const resolvedErrors = Object.keys(errors).length > 0
			? errors
			: incomingErrors

		await runHooks('validation-error', options.hooks, resolvedErrors, request, context)
	} else {
		await runHooks('success', options.hooks, payload, request, response, context)
	}

	return { response }
}

/** Checks if the response contains a hybrid header. */
export function isHybridResponse(response: HttpResponse): boolean {
	return response.headers.has(HYBRIDLY_HEADER)
}

function isPartial(options: HybridRequestOptions) {
	return options.only !== undefined || options.except !== undefined || options.reset !== undefined
}

function resolveProperties(original: Properties, payload: View, options: { mergeWithOriginal: boolean }) {
	const mergeable = payload.mergeable ?? []
	const mergedPayloadProperties = options.mergeWithOriginal
		? merge(original, payload.properties)
		: payload.properties

	// Mergeable properties are properties that will be merged with the original ones instead
	// of replacing them. They can be merged at the root level or at a specific path.
	for (const [property, shouldPrepend, uniqueBy, mergePaths] of mergeable) {
		const originalValue = get(original, property) as unknown
		const newValue = get(payload.properties, property) as unknown

		if (!options.mergeWithOriginal && newValue === undefined) {
			continue
		}

		const value = mergeMergeableProperty(
			originalValue,
			newValue,
			get(mergedPayloadProperties, property) as unknown,
			mergePaths,
			{ prepend: shouldPrepend, uniqueBy },
		)

		set(mergedPayloadProperties, property, value)
	}

	return mergedPayloadProperties
}

function mergeMergeableProperty(
	originalValue: unknown,
	newValue: unknown,
	currentValue: unknown,
	mergePaths: string[] | null | undefined,
	options: { prepend: boolean; uniqueBy: string | null },
) {
	if (!mergePaths?.length) {
		return mergeMergeableValue(originalValue, newValue, options)
	}

	const value = currentValue instanceof Object
		? currentValue as Properties
		: {}

	for (const mergePath of mergePaths) {
		set(
			value,
			mergePath,
			mergeMergeableValue(
				get(originalValue, mergePath) as unknown,
				get(newValue, mergePath) as unknown,
				options,
			),
		)
	}

	return value
}

function mergeMergeableValue(
	originalValue: unknown,
	newValue: unknown,
	options: { prepend: boolean; uniqueBy: string | null },
) {
	if (Array.isArray(originalValue)) {
		const incoming = Array.isArray(newValue)
			? newValue
			: newValue === undefined
			? []
			: [newValue]

		return mergeMergeableArrays(originalValue, incoming, options)
	}

	if (originalValue instanceof Object && newValue instanceof Object) {
		return merge(originalValue as Properties, newValue as Properties, {
			overwriteArray: false,
			arrayMerge: (current, incoming) => mergeMergeableArrays(current, incoming, options),
		})
	}

	return newValue
}

function mergeMergeableArrays(
	current: unknown[],
	incoming: unknown[],
	options: { prepend: boolean; uniqueBy: string | null },
) {
	const merged = options.prepend
		? [...incoming, ...current]
		: [...current, ...incoming]

	if (typeof options.uniqueBy !== 'string') {
		return merged
	}

	const getUniqueKey = (entry: unknown) => {
		const key = get(entry, options.uniqueBy!)
		return key === undefined ? Symbol() : key
	}

	if (options.prepend) {
		return uniqBy(merged, getUniqueKey)
	}

	const orderedKeys: unknown[] = []
	const valuesByKey = new Map<unknown, unknown>()

	for (const entry of merged) {
		const key = getUniqueKey(entry)

		if (!valuesByKey.has(key)) {
			orderedKeys.push(key)
		}

		valuesByKey.set(key, entry)
	}

	return orderedKeys.map((key) => valuesByKey.get(key)!)
}

function mergeValidation(current: Validation, next: Validation, errorBag?: string): Validation {
	if (!errorBag) {
		return next
	}

	return {
		...current,
		[errorBag]: next[errorBag] ?? {},
	}
}

function resolveErrors(validation: Validation, errorBag?: string): Errors {
	if (errorBag) {
		return validation[errorBag] ?? {}
	}

	return validation.default ?? {}
}
