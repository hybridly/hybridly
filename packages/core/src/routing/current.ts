import { getInternalRouterContext } from '../context'
import { getNameFromUrl, getRouting, urlMatchesRoute } from './route'
import type { RouteName, RouteParameters } from './index'

function getCurrentUrl() {
	// If we are on the server, we use the internal router context (SSR)
	if (typeof window === 'undefined') {
		return getInternalRouterContext().url
	}

	return window.location.toString()
}

export function isCurrentFromName<T extends RouteName>(name: T, parameters?: RouteParameters<T>): boolean {
	// We escape all dots and replace all stars with a regex that matches any character sequence to build the regex
	const namePattern = name.replaceAll('.', '\\.').replaceAll('*', '.*')
	const possibleRoutes = Object.values(getRouting().routes)
		.filter((x) => x.method.includes('GET') && RegExp(namePattern).test(x.name))
		.map((x) => x.name)
	const currentUrl = getCurrentUrl()

	return possibleRoutes.some((routeName) => {
		return urlMatchesRoute(currentUrl, routeName, parameters)
	})
}

export function getCurrentRouteName<T extends RouteName>(): T | undefined {
	return getNameFromUrl<T>(getCurrentUrl())
}
