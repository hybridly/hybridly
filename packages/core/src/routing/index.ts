import { setContext } from '../context'
import { Router } from './router'
import type { RoutingConfiguration, RouteName, RouteParameters } from './types'

/**
 * Generates a route from the given route name.
 */
export function route<T extends RouteName>(name: T, parameters?: RouteParameters<T>, absolute?: boolean) {
	return new Router<T>(name, parameters, absolute).toString()
}

export function updateRoutingConfiguration(routing?: RoutingConfiguration) {
	if (!routing) {
		return
	}

	setContext({ routing })
}

export * from './types'
