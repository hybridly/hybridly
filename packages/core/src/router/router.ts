import { debug } from '@hybridly/utils'
import type { InternalRouterContext, RouterContextOptions } from '../context'
import { getRouterContext, initializeContext, setContext } from '../context'
import { makeUrl, sameUrls } from '../url'
import { runHooks } from '../plugins'
import { generateRouteFromName, getRouteDefinition } from '../routing/route'
import { closeDialog } from '../dialog'
import { currentRouteMatches, getCurrentRouteName } from '../routing/current'
import { handleExternalNavigation, isExternalNavigation, navigateToExternalUrl } from './response/external'
import { getHistoryMemo, handleBackForwardNavigation, isBackForwardNavigation, registerEventListeners, remember } from './history'
import type { Router } from './types'
import { performHybridNavigation } from './request/request'
import { navigate, performLocalNavigation } from './view'
import { cancelSyncRequest } from './request/request-stack'

/**
 * The hybridly router.
 * This is the core function that you can use to navigate in
 * your application. Make sure the routes you call return a
 * hybrid response, otherwise you need to call `external`.
 *
 * @example
 * router.get('/posts/edit', { post })
 */
export const router = {
	abort: () => cancelSyncRequest(),
	navigate: async (options) => await performHybridNavigation(options),
	reload: async (options) => await performHybridNavigation({ preserveScroll: true, preserveState: true, replace: true, async: true, ...options }),
	get: async (url, options = {}) => await performHybridNavigation({ ...options, url, method: 'GET' }),
	post: async (url, options = {}) => await performHybridNavigation({ preserveState: true, ...options, url, method: 'POST' }),
	put: async (url, options = {}) => await performHybridNavigation({ preserveState: true, ...options, url, method: 'PUT' }),
	patch: async (url, options = {}) => await performHybridNavigation({ preserveState: true, ...options, url, method: 'PATCH' }),
	delete: async (url, options = {}) => await performHybridNavigation({ preserveState: true, ...options, url, method: 'DELETE' }),
	local: async (url, options = {}) => await performLocalNavigation(url, options),
	// preload: async (url, options = {}) => await performPreloadRequest({ ...options, url, method: 'GET' }),
	external: (url, data = {}) => navigateToExternalUrl(url, data),
	to: async (name, parameters, options) => {
		const url = generateRouteFromName(name, parameters)
		const method = getRouteDefinition(name).method.at(0)
		return await performHybridNavigation({ url, ...options, method })
	},
	matches: (name, parameters) => currentRouteMatches(name, parameters),
	current: () => getCurrentRouteName(),
	dialog: {
		close: (options = {}) => closeDialog(options),
	},
	history: {
		get: (key) => getHistoryMemo(key),
		remember: (key, value) => remember(key, value),
	},
} satisfies Router

/** Creates the hybridly router. */
export async function createRouter(options: RouterContextOptions): Promise<InternalRouterContext> {
	await initializeContext(options)

	return await initializeRouter()
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
