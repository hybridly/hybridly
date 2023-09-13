import type { AxiosResponse } from 'axios'
import qs from 'qs'
import { debug } from '@hybridly/utils'
import type { HybridRequestOptions } from 'hybridly'
import { EXTERNAL_NAVIGATION_HEADER, STORAGE_EXTERNAL_KEY } from './constants'
import { getRouterContext, setContext } from './context'
import { navigate } from './router/router'
import type { UrlResolvable } from './url'
import { makeUrl, sameUrls } from './url'

/**
 * Performs an external navigation by saving options to the storage and
 * making a full page reload. Upon loading, the navigation options
 * will be pulled and a hybrid navigation will be made.
 */
export async function performExternalNavigation(options: ExternalNavigationOptions): Promise<void> {
	debug.external('Navigating to an external URL:', options)
	window.sessionStorage.setItem(STORAGE_EXTERNAL_KEY, JSON.stringify(options))
	window.location.href = options.url

	// If the external navigation is to the same page, we need to manually perform
	// a full page reload
	if (sameUrls(window.location, options.url)) {
		debug.external('Manually reloading due to the external URL being the same.')
		window.location.reload()
	}
}

/** Navigates to the given URL without the hybrid protocol. */
export function navigateToExternalUrl(url: UrlResolvable, data?: HybridRequestOptions['data']) {
	document.location.href = makeUrl(url, {
		search: qs.stringify(data, {
			encodeValuesOnly: true,
			arrayFormat: 'brackets',
		}),
	}).toString()
}

/** Checks if the response wants to redirect to an external URL. */
export function isExternalResponse(response: AxiosResponse): boolean {
	return response?.status === 409 && !!response?.headers?.[EXTERNAL_NAVIGATION_HEADER]
}

/**
 * Performs the internal navigation when an external navigation to a hybrid page has been made.
 * This method is meant to be called on router creation.
 */
export async function handleExternalNavigation(): Promise<void> {
	debug.external('Handling an external navigation.')
	const options = JSON.parse(window.sessionStorage.getItem(STORAGE_EXTERNAL_KEY) || '{}') as ExternalNavigationOptions
	window.sessionStorage.removeItem(STORAGE_EXTERNAL_KEY)

	debug.external('Options from the session storage:', options)

	// If we navigated to somewhere with a hash, we need to update the context
	// to add said hash because it was initialized without it.
	setContext({
		url: makeUrl(getRouterContext().url, { hash: window.location.hash }).toString(),
	})

	await navigate({
		type: 'initial',
		preserveState: true,
		preserveScroll: options.preserveScroll,
	})
}

/** Checks if the navigation being initialized points to an external location. */
export function isExternalNavigation(): boolean {
	try {
		return window.sessionStorage.getItem(STORAGE_EXTERNAL_KEY) !== null
	} catch {}

	return false
}

interface ExternalNavigationOptions {
	/** Target URL. */
	url: string
	/** Whether to preserve the scroll if the external navigation leads to a hybrid view. */
	preserveScroll: boolean
}
