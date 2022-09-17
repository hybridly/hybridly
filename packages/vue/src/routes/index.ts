import { Router } from './router'
import type { RouteName, RouteParameters } from './types'

// Credits to Ziggy
// @see https://github.com/tighten/ziggy

/**
 * Generates a route from the given route name.
 */
export function route<T extends RouteName>(name: T, parameters?: RouteParameters<T>, absolute?: boolean) {
	return new Router<T>(name, parameters, absolute).toString()
}

export * from './types'
