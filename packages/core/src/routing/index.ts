import { setContext } from '../context'
import { isCurrentFromName } from './current'
import { generateRouteFromName } from './route'
import type { RoutingConfiguration, RouteName, RouteParameters } from './types'

/**
 * Generates a route from the given route name.
 */
export function route<T extends RouteName>(name: T, parameters?: RouteParameters<T>, absolute?: boolean) {
	return generateRouteFromName(name, parameters, absolute)
}

/**
 * Determines if the current route correspond to the given route name and parameters.
 */
export function current<T extends RouteName>(name: T, parameters?: RouteParameters<T>): boolean {
	return isCurrentFromName(name, parameters)
}

export function updateRoutingConfiguration(routing?: RoutingConfiguration) {
	if (!routing) {
		return
	}

	setContext({ routing })
}

export * from './types'
