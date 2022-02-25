export type UrlResolvable = string | URL | Location

/** Normalizes the given input to an URL. */
export function normalizeUrl(href: UrlResolvable): string {
	return new URL(String(href)).toString()
}

/**
 * Converts an input to an URL, optionally changing its properties after initialization.
 */
export function makeUrl(href: UrlResolvable, props: Partial<Record<keyof URL, any>> = {}): URL {
	try {
		const url = new URL(String(href))
		Object.entries(props ?? {}).forEach(([key, value]) => Reflect.set(url, key, value))

		return url
	} catch (error) {
		throw new TypeError(`${href} is not resolvable to a valid URL.`)
	}
}

/**
 * Checks if the given URLs have the same origin.
 */
export function sameOrigin(...hrefs: UrlResolvable[]): boolean {
	if (hrefs.length < 2) {
		return true
	}

	try {
		return hrefs.every((href) => {
			return makeUrl(href, { hash: '' }).toJSON() === makeUrl(hrefs.at(0)!, { hash: '' }).toJSON()
		})
	} catch {}

	return false
}

/**
 * If the back-end did not specify a hash, if the visit specified one,
 * and that both URLs lead to the same endpoint, we update the target URL
 * to use the hash of the initially-requested URL.
 * This behavior originates from Inertia.
 */
export function fillHash(currentUrl: UrlResolvable, targetUrl: UrlResolvable): string {
	currentUrl = makeUrl(currentUrl)
	targetUrl = makeUrl(targetUrl)

	if (currentUrl.hash && !targetUrl.hash && sameOrigin(targetUrl, currentUrl)) {
		targetUrl.hash = currentUrl.hash
	}

	return targetUrl.toString()
}
