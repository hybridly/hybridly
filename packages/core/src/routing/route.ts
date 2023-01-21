import qs from 'qs'
import { getInternalRouterContext } from '../context'
import { MissingRouteParameter, RouteNotFound, RoutingNotInitialized } from '../errors'
import type { UrlTransformable } from '../url'
import { makeUrl } from '../url'
import type { RouteDefinition, RouteName, RouteParameters, RoutingConfiguration } from './types'

export function generateRouteFromName<T extends RouteName>(name: T, parameters?: RouteParameters<T>, absolute?: boolean, shouldThrow?: boolean) {
	const url = getUrlFromName(name, parameters, shouldThrow)

	return absolute === false
		? url.toString().replace(url.origin, '')
		: url.toString()
}

export function getUrlFromName<T extends RouteName>(name: T, parameters?: RouteParameters<T>, shouldThrow?: boolean) {
	const routing = getRouting()
	const definition = getRouteDefinition(name)
	const transforms = getRouteTransformable(name, parameters, shouldThrow)
	const url = makeUrl(routing.url, (url) => ({
		hostname: definition.domain || url.hostname,
		port: routing.port?.toString() || url.port,
		trailingSlash: false,
		...transforms,
	}))

	return url
}

/**
 * Gets the `UrlTransformable` object for the given route and parameters.
 */
function getRouteTransformable(routeName: string, routeParameters?: any, shouldThrow?: boolean): UrlTransformable {
	const routing = getRouting()
	const definition = getRouteDefinition(routeName)
	const parameters = routeParameters || {}
	const missing: string[] = Object.keys(parameters)
	const path = definition.uri.replace(/{([^}?]+)\??}/g, (match: string, parameterName: string) => {
		const optional = /\?}$/.test(match)
		const value = (() => {
			const value = parameters[parameterName]
			const bindingProperty = definition.bindings?.[parameterName]

			if (bindingProperty && typeof value === 'object') {
				return value[bindingProperty]
			}

			return value
		})()

		// Removes this parameter from the missing parameter list.
		missing.splice(missing.indexOf(parameterName), 1)

		// If the parameter is passed, use it.
		if (value) {
			const where = definition.wheres?.[parameterName]

			// If the parameter doesn't respect the format, warn.
			if (where && !(new RegExp(where).test(value))) {
				console.warn(`[hybridly:routing] Parameter [${parameterName}] does not match the required format [${where}] for route [${routeName}].`)
			}

			return value
		}

		// If there is a default parameter, use it.
		if (routing.defaults?.[parameterName]) {
			return routing.defaults?.[parameterName]
		}

		// Otherwise, if it was optional, return an empty string.
		if (optional) {
			return ''
		}

		if (shouldThrow === false) {
			return ''
		}

		throw new MissingRouteParameter(parameterName, routeName)
	})

	// Filters from `parameters` the values from `missing`
	// and returns a new object with the remaining values.
	const remaining = Object.keys(parameters)
		.filter((key) => missing.includes(key))
		.reduce((obj, key) => ({
			...obj,
			[key]: parameters[key],
		}), {})

	return {
		pathname: path,
		search: qs.stringify(remaining, {
			encodeValuesOnly: true,
			arrayFormat: 'indices',
			addQueryPrefix: true,
		}),
	}
}

/**
 * Gets the route definition.
 */
export function getRouteDefinition(name: string): RouteDefinition {
	const routing = getRouting()

	const definition = routing.routes[name]

	if (!definition) {
		throw new RouteNotFound(name)
	}

	return definition
}

/**
 * Gets the routing configuration from the current context.
 */
export function getRouting(): RoutingConfiguration {
	const { routing } = getInternalRouterContext()

	if (!routing) {
		throw new RoutingNotInitialized()
	}

	return routing
}
