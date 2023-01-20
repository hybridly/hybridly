import { makeUrl } from '../url'
import { generateRouteFromName } from './route'
import type { RouteName, RouteParameters } from './index'

export function isCurrentFromName<T extends RouteName>(name: T, parameters?: RouteParameters<T>): boolean {
	const location = window.location
	const matchee = (() => {
		try {
			return makeUrl(generateRouteFromName(name, parameters, true, false))
		} catch (error) {}
	})()

	if (!matchee) {
		return false
	}

	return location.href.startsWith(matchee.href)
}
