import { debug } from '@hybridly/utils'
import type { AxiosResponse } from 'axios'
import { getInternalRouterContext, getRouterContext } from '../context'
import { makeUrl } from '../url'
import type { HybridRequestOptions } from './types'
import { isHybridResponse, performHybridRequest } from './router'

/**
 * Checks if there is a preloaded request for the given URL.
 */
export function isPreloaded(targetUrl: URL): boolean {
	const context = getInternalRouterContext()
	return context.preloadCache.has(targetUrl.toString()) ?? false
}

/**
 * Gets and erases a preloaded request.
 */
export function pullPreloadedRequest(targetUrl: URL): AxiosResponse | undefined {
	const response = getPreloadedRequest(targetUrl)
	discardPreloadedRequest(targetUrl)

	return response
}

/**
 * Gets the response of a preloaded request.
 */
export function getPreloadedRequest(targetUrl: URL): AxiosResponse | undefined {
	const context = getInternalRouterContext()
	return context.preloadCache.get(targetUrl.toString())
}

/**
 * Stores the response of a preloaded request.
 */
export function storePreloadRequest(targetUrl: URL, response: AxiosResponse) {
	const context = getInternalRouterContext()
	context.preloadCache.set(targetUrl.toString(), response)
}

/**
 * Discards a preloaded request.
 */
export function discardPreloadedRequest(targetUrl: URL) {
	const context = getInternalRouterContext()
	return context.preloadCache.delete(targetUrl.toString())
}

/** Preloads a hybrid request. */
export async function performPreloadRequest(options: HybridRequestOptions): Promise<boolean> {
	const context = getRouterContext()
	const url = makeUrl(options.url ?? context.url)

	if (isPreloaded(url)) {
		debug.router('This request is already preloaded.')
		return false
	}

	if (context.pendingNavigation) {
		debug.router('A navigation is pending, preload aborted.')
		return false
	}

	if (options.method !== 'GET') {
		debug.router('Cannot preload non-GET requests.')
		return false
	}

	debug.router(`Preloading response for [${url.toString()}]`)

	try {
		const response = await performHybridRequest(url, options)

		if (!isHybridResponse(response)) {
			debug.router('Preload result was invalid.')
			return false
		}

		storePreloadRequest(url, response)
		return true
	} catch (error) {
		debug.router('Preloading failed.')
		return false
	}
}
