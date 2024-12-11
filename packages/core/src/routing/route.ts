import qs from 'qs'
import { getInternalRouterContext } from '../context'
import { MissingRouteParameter, RouteNotFound, RoutingNotInitialized } from '../errors'
import type { UrlTransformable } from '../url'
import { makeUrl } from '../url'
import type { RouteDefinition, RouteName, RouteParameters, RoutingConfiguration } from './types'

function getUrlRegexForRoute<T extends RouteName>(name: T) {
	const routing = getRouting()
	const definition = getRouteDefinition(name)

	// escape slashes for regex in route path uri
	const path = definition.uri.replaceAll('/', '\\/')
	const domain = definition.domain
	const protocolPrefix = routing.url.match(/^\w+:\/\//)?.[0]

	// We build the origin based on the routing configuration and escape all slashes.
	// Note: Only for custom domain routes, the port configuration is applied.
	const origin = domain
		? `${protocolPrefix}${domain}${routing.port ? `:${routing.port}` : ''}`.replaceAll('/', '\\/')
		: routing.url.replaceAll('/', '\\/')

	// We need to make sure only to prepend a slash when a path is actually supplied
	const urlPathRegexPattern = path.length > 0 ? `\\/${path.replace(/\/$/g, '')}` : ''

	let urlRegexPattern = `^${origin.replaceAll('.', '\\.')}${urlPathRegexPattern}\\/?(\\?.*)?$`

	// We now need to replace all parameters with their regex in the entire url regex pattern.
	// This includes path parameters and domain parameters, eg. for dynamic subdomains.
	//
	// Captures the following groups for parameter templates
	// 1. (optionally) the initial (already escaped) slash, which is needed for optional parameters regex, where the slash will also need to be optional
	// 2. The parameter name
	// 3. (optionally) tries to find a trailing `?`, which marks the parameter as optional
	urlRegexPattern = urlRegexPattern.replace(/(\\\/?){([^}?]+)(\??)}/g, (_, slash: string | null, parameterName: string, optional: string | null) => {
		const where = definition.wheres?.[parameterName]

		// Use a concrete parameter instance when available
		let regexTemplate = where?.replace(/(^\^)|(\$$)/g, '') || '[^/?]+'

		// Name the capture group after the parameter name
		regexTemplate = `(?<${parameterName}>${regexTemplate})`

		if (optional) {
			// We need to make the slash optional as well, since urls without the slash should be matched as well.
			return `(${slash ? '\\/?' : ''}${regexTemplate})?`
		}

		// We need to prepend the slash again, if it was matched, otherwise it would be removed.
		return (slash ? '\\/' : '') + regexTemplate
	})

	return RegExp(urlRegexPattern)
}

/**
 * Check if a given URL matches a route based on its name.
 * Additionally you can pass an object of parameters to check if the URL matches the route with the given parameters.
 * Otherwise it will accept and thus return true for any values for the parameters defined by the route.
 * Note: passing additional parameters that are not defined by the route or included in the current URL will cause this to return false.
 */
export function urlMatchesRoute<T extends RouteName>(fullUrl: string, name: T, routeParameters?: RouteParameters<T>): boolean {
	const url = makeUrl(fullUrl, { hash: '' }).toString()
	const parameters = routeParameters || {}
	const definition = getRouting().routes[name]

	if (!definition) {
		return false
	}

	// test for regex
	const matches = getUrlRegexForRoute(name).exec(url)

	if (!matches) {
		return false
	}

	for (const k in matches.groups) {
		matches.groups[k] = typeof matches.groups[k] === 'string' ? decodeURIComponent(matches.groups[k]) : matches.groups[k]
	}

	// Note: this will not be true when additional parameters are passed
	return Object.keys(parameters).every((parameterName: string) => {
		let value = parameters[parameterName]
		const bindingProperty = definition.bindings?.[parameterName]

		if (bindingProperty && typeof value === 'object') {
			value = value[bindingProperty]
		}

		return matches.groups?.[parameterName] === value.toString()
	})
}

export function generateRouteFromName<T extends RouteName>(name: T, parameters?: RouteParameters<T>, absolute?: boolean, shouldThrow?: boolean) {
	const url = getUrlFromName(name, parameters, shouldThrow)

	return absolute === false
		? url.toString().replace(url.origin, '')
		: url.toString()
}

export function getNameFromUrl<T extends RouteName>(url: string, parameters?: RouteParameters<T>): T | undefined {
	const routing = getRouting()
	const routes = Object.values(routing.routes).map((x) => x.name as T)

	return routes.find((routeName) => {
		return urlMatchesRoute(url, routeName, parameters)
	})
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
 * Resolved the value of a route parameter from either the passed parameters or the default parameters.
 */
function getRouteParameterValue(routeName: string, parameterName: string, routeParameters?: Record<string, any>) {
	const routing = getRouting()
	const definition = getRouteDefinition(routeName)
	const parameters = routeParameters || {}

	const value = (() => {
		const value = parameters[parameterName]
		const bindingProperty = definition.bindings?.[parameterName]

		if (bindingProperty && value != null && typeof value === 'object') {
			return value[bindingProperty]
		}

		return value
	})()

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
}

/**
 * Gets the `UrlTransformable` object for the given route and parameters.
 */
function getRouteTransformable(routeName: string, routeParameters?: any, shouldThrow?: boolean): UrlTransformable {
	const definition = getRouteDefinition(routeName)
	const parameters = routeParameters || {}
	const missing: string[] = Object.keys(parameters)
	const replaceParameter = (match: string, parameterName: string, optional: string | null) => {
		const value = getRouteParameterValue(routeName, parameterName, parameters)

		const found = missing.indexOf(parameterName)
		// Removes this parameter from the missing parameter list.
		if (found >= 0) {
			missing.splice(found, 1)
		}
		// If the parameter is passed, use it.
		if (value) {
			return value
		}

		// Otherwise, if it was optional, return an empty string.
		if (optional) {
			return ''
		}

		if (shouldThrow === false) {
			return ''
		}

		throw new MissingRouteParameter(parameterName, routeName)
	}

	const path = definition.uri.replace(/{([^}?]+)(\??)}/g, replaceParameter)
	const domain = definition.domain?.replace(/{([^}?]+)(\??)}/g, replaceParameter)

	// Filters from `parameters` the values from `missing`
	// and returns a new object with the remaining values.
	const remaining = Object.keys(parameters)
		.filter((key) => missing.includes(key))
		.reduce((obj, key) => ({
			...obj,
			[key]: parameters[key],
		}), {})

	return {
		...domain && { hostname: domain },
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

/**
 * Generates a route from the given route name.
 */
export function route<T extends RouteName>(name: T, parameters?: RouteParameters<T>, absolute?: boolean) {
	return generateRouteFromName(name, parameters, absolute)
}
