import qs from 'qs'
import { merge } from '@hybridly/utils'

export type UrlResolvable = string | URL | Location
export type UrlTransformable = Partial<Omit<URL, 'searchParams' | 'toJSON' | 'toString'>> & {
	query?: any
}

/** Normalizes the given input to an URL. */
export function normalizeUrl(href: UrlResolvable): string {
	return makeUrl(href).toString()
}

/**
 * Converts an input to an URL, optionally changing its properties after initialization.
 */
export function makeUrl(href: UrlResolvable, transformations: UrlTransformable = {}): URL {
	try {
		// Workaround for testing. For some reason happy-dom fills this
		// to double slashes, which breaks URL instanciation.
		const base = document?.location?.href === '//' ? undefined : document.location.href
		const url = new URL(String(href), base)
		Object.entries(transformations ?? {}).forEach(([key, value]) => {
			if (key === 'query') {
				key = 'search'
				value = qs.stringify(merge(qs.parse(url.search, { ignoreQueryPrefix: true }), value), {
					encodeValuesOnly: true,
					arrayFormat: 'brackets',
				})
			}

			Reflect.set(url, key, value)
		})

		return url
	} catch (error) {
		throw new TypeError(`${href} is not resolvable to a valid URL.`)
	}
}

/**
 * Checks if the given URLs have the same origin and path.
 */
export function sameUrls(...hrefs: UrlResolvable[]): boolean {
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
 * If the back-end did not specify a hash, if the navigation specified one,
 * and both URLs lead to the same endpoint, we update the target URL
 * to use the hash of the initially-requested URL.
 */
export function fillHash(currentUrl: UrlResolvable, targetUrl: UrlResolvable): string {
	currentUrl = makeUrl(currentUrl)
	targetUrl = makeUrl(targetUrl)

	if (currentUrl.hash && !targetUrl.hash && sameUrls(targetUrl, currentUrl)) {
		targetUrl.hash = currentUrl.hash
	}

	return targetUrl.toString()
}
