export const STORAGE_EXTERNAL_KEY = 'sleightful:external'

/**
 * Checks if the current visit was made by going back or forward.
 */
export function isBackForwardVisit(): boolean {
	if (!window.history.state) {
		return false
	}

	return (window.performance?.getEntriesByType('navigation').at(0) as PerformanceNavigationTiming)?.type === 'back_forward'
}

/**
 * Checks if the current visist points to an external location.
 */
export function isExternalVisit(): boolean {
	try {
		return window.sessionStorage.getItem(STORAGE_EXTERNAL_KEY) !== null
	} catch {}

	return false
}

/**
 * Gets the current visit type.
 */
export function getCurrentVisitType(): 'back_forward' | 'external' | 'sleightful' {
	if (isBackForwardVisit()) {
		return 'back_forward'
	}

	if (isExternalVisit()) {
		return 'external'
	}

	return 'sleightful'
}

/**
 * Convers the given string or URL to an URL.
 */
export function makeUrl(href: string | URL): URL {
	return new URL(href.toString(), window.location.toString())
}
