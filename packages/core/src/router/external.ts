import { AxiosResponse } from 'axios'
import { EXTERNAL_VISIT_HEADER, STORAGE_EXTERNAL_KEY } from '../constants'
import { debug } from '../utils'
import { RouterContext, setContext } from './context'
import { navigate } from './router'
import { makeUrl, sameUrls } from './url'

/**
 * Performs an external visit by saving options to the storage and
 * making a full page reload. Upon loading, the visit options will be pulled
 * and a monolikit navigation will be performed.
 */
export async function performExternalVisit(options: ExternalVisitOptions): Promise<void> {
	debug.external('Making a hard navigation for an external visit:', options)
	window.sessionStorage.setItem(STORAGE_EXTERNAL_KEY, JSON.stringify(options))
	window.location.href = options.url

	// If the external visit is to the same page, we need to manually perform
	// a full page reload
	if (sameUrls(window.location, options.url)) {
		debug.external('Manually reloading due to the external URL being the same.')
		window.location.reload()
	}
}

/** Checks if the response wants to redirect to an external URL. */
export function isExternalResponse(response: AxiosResponse): boolean {
	return response?.status === 409 && !!response?.headers?.[EXTERNAL_VISIT_HEADER]
}

/**
 * Performs the internal navigation when an external visit to a monolikit page
 * has been made.
 * This method is meant to be called on router creation.
 */
export async function handleExternalVisit(context: RouterContext): Promise<void> {
	debug.external('Handling an external visit.')
	const options = JSON.parse(window.sessionStorage.getItem(STORAGE_EXTERNAL_KEY) || '{}') as ExternalVisitOptions
	window.sessionStorage.removeItem(STORAGE_EXTERNAL_KEY)

	debug.external('Options from the session storage:', options)

	// If we navigated to somewhere with a hash, we need to update the context
	// to add said hash because it was initialized without it.
	setContext(context, {
		url: makeUrl(context.url, { hash: window.location.hash }).toString(),
	})

	// TODO: add history state to context?

	await navigate(context, {
		preserveScroll: options.preserveScroll,
		preserveState: true,
	})
}

/** Checks if the visit being initialized points to an external location. */
export function isExternalVisit(): boolean {
	try {
		return window.sessionStorage.getItem(STORAGE_EXTERNAL_KEY) !== null
	} catch {}

	return false
}

interface ExternalVisitOptions {
	/** Target URL. */
	url: string
	/** Whether to preserve the scroll if the external visit leads to a monolikit view. */
	preserveScroll: boolean
}
