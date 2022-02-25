import { AxiosResponse } from 'axios'
import { EXTERNAL_VISIT_HEADER, STORAGE_EXTERNAL_KEY } from '../constants'
import { RouterContext, setContext } from './context'
import { navigate } from './router'
import { makeUrl, sameOrigin } from './url'

/**
 * Performs an external visit by saving options to the storage and
 * making a full page reload. Upon loading, the visit options will be pulled
 * and a sleightul navigation will be performed.
 */
export async function performExternalVisit(options: ExternalVisitOptions): Promise<void> {
	window.sessionStorage.setItem(STORAGE_EXTERNAL_KEY, JSON.stringify(options))
	window.location.href = options.url

	// If the external visit is to the same page, we need to manually perform
	// a full page reload
	if (sameOrigin(window.location, options.url)) {
		window.location.reload()
	}
}

interface ExternalVisitOptions {
	/** Target URL. */
	url: string
	/** Whether to preserve the scroll if the external visit leads to a sleightful view. */
	preserveScroll: boolean
}

/** Checks if the response wants to redirect to an external URL. */
export function isExternalVisitResponse(response: AxiosResponse): boolean {
	return response?.status === 409 && !!response?.headers[EXTERNAL_VISIT_HEADER]
}

/**
 * Performs the internal navigation when an external visit to a sleightful page
 * has been made.
 * This method is meant to be called on router creation.
 */
export async function handleExternalVisit(context: RouterContext): Promise<void> {
	const options = JSON.parse(window.sessionStorage.getItem(STORAGE_EXTERNAL_KEY) || '{}') as ExternalVisitOptions
	window.sessionStorage.removeItem(STORAGE_EXTERNAL_KEY)

	// If we navigated to somewhere with a hash, we need to update the context
	// to add said hash because it was initialized without it.
	setContext(context, {
		url: makeUrl(context.url, { hash: window.location.hash }).toString(),
	})

	// TODO: add history state to context?

	await navigate(context, {
		request: context,
		preserveScroll: options.preserveScroll,
		preserveState: true,
	})
}
