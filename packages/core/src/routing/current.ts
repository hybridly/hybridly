import { makeUrl } from '../url'
import { generateRouteFromName } from './route'
import type { RouteName, RouteParameters } from './index'

export function isCurrentFromName<T extends RouteName>(name: T, parameters?: RouteParameters<T>, mode: 'loose' | 'strict' = 'loose'): boolean {
	const location = window.location
	const matchee = (() => {
		try {
			return makeUrl(generateRouteFromName(name, parameters, true, false))
		} catch (error) {}
	})()

	if (!matchee) {
		return false
	}

	if (mode === 'strict') {
		return location.href === matchee.href
	}

	return location.href.startsWith(matchee.href)
}
