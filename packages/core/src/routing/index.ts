import { setContext } from '../context'
import { getCurrentRouteName, isCurrentFromName } from './current'
import { generateRouteFromName } from './route'
import type { RoutingConfiguration, RouteName, RouteParameters } from './types'

/**
 * Generates a route from the given route name.
 */
export function route<T extends RouteName>(name: T, parameters?: RouteParameters<T>, absolute?: boolean) {
	return generateRouteFromName(name, parameters, absolute)
}

/**
 * Returns the current route name or undefined if it matches none.
 *
 * Note: This will return the first matching route name if multiple routes match the current url.
 *
 * This should not be a problem during normal usage, but it can be a problem once you have overlapping routes.
 */
export function current<T extends RouteName>(): T | undefined
/**
 * Determines if the current route corresponds to the given route name and parameters.
 *
 * You can also use asterisks to match route names based on a pattern.
 *
 * @example
 * ```ts
 * current('tenant.*') // matches all routes starting with 'tenant.'
 * current('tenant.*.admin') // matches all routes starting with 'tenant.' and ending with '.admin'
 * ```
 */
export function current<T extends RouteName>(name: T, parameters?: RouteParameters<T>): boolean
export function current<T extends RouteName>(name?: T, parameters?: RouteParameters<T>): T | undefined | boolean {
	if (name === undefined) {
		return getCurrentRouteName<T>()
	}

	return isCurrentFromName(name, parameters)
}

export function updateRoutingConfiguration(routing?: RoutingConfiguration) {
	if (!routing) {
		return
	}

	setContext({ routing })
}

export * from './types'
