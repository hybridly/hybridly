import { parse } from 'qs'
import { state } from '../stores/state'
import { RouteDefinition, RouteName } from './types'

/**
 * A Laravel route. This class represents one route and its configuration and metadata.
 */
export class Route {
	public definition: RouteDefinition

	constructor(
		public name: RouteName,
		public absolute: boolean,
	) {
		this.definition = Route.getDefinition(name)
	}

	static getDefinition(name: RouteName): RouteDefinition {
		if (!state.routes.value) {
			throw new Error('Routing is not initialized. Have you enabled the Vite plugin?')
		}

		const routes = state.routes.value as any
		const route = routes?.routes?.[name]

		if (!route) {
			throw new Error(`Route ${name.toString()} does not exist.`)
		}

		return route
	}

	/**
	 * Gets a 'template' of the complete URL for this route.
	 *
	 * @example
	 * https://{team}.ziggy.dev/user/{user}
	 */
	get template(): string {
		// If  we're building just a path there's no origin, otherwise: if this route has a
		// domain configured we construct the origin with that, if not we use the app URL
		const origin = !this.absolute
			? ''
			: this.definition.domain
				? `${state.routes.value?.url.match(/^\w+:\/\//)?.[0]}${this.definition.domain}${state.routes.value?.port ? `:${state.routes.value?.port}` : ''}`
				: state.routes.value?.url

		return `${origin}/${this.definition.uri}`.replace(/\/+$/, '')
	}

	/**
	 * Gets an array of objects representing the parameters that this route accepts.
	 *
	 * @example
	 * [{ name: 'team', required: true }, { name: 'user', required: false }]
	 */
	get parameterSegments() {
		return this.template.match(/{[^}?]+\??}/g)?.map((segment) => ({
			name: segment.replace(/{|\??}/g, ''),
			required: !/\?}$/.test(segment),
		})) ?? []
	}

	/**
	 * Gets whether this route's template matches the given URL.
	 */
	matchesUrl(url: string): object | false {
		if (!this.definition.methods.includes('GET')) {
			return false
		}

		// Transform the route's template into a regex that will match a hydrated URL,
		// by replacing its parameter segments with matchers for parameter values
		const pattern = this.template
			.replace(/(\/?){([^}?]*)(\??)}/g, (_, slash, segment, optional) => {
				const regex = `(?<${segment}>${this.definition.wheres?.[segment]?.replace(/(^\^)|(\$$)/g, '') || '[^/?]+'})`
				return optional ? `(${slash}${regex})?` : `${slash}${regex}`
			})
			.replace(/^\w+:\/\//, '')

		const [location, query] = url.replace(/^\w+:\/\//, '').split('?')
		const matches = new RegExp(`^${pattern}/?$`).exec(location)

		return matches
			? { params: matches.groups, query: parse(query) }
			: false
	}

	/**
	 * Hydrates and return a complete URL for this route with the given parameters.
	 */
	compile(params: Record<string, any>): string {
		const segments = this.parameterSegments

		if (!segments.length) {
			return this.template
		}

		return this.template.replace(/{([^}?]+)(\??)}/g, (_, segment, optional) => {
			// If the parameter is missing but is not optional, throw an error
			if (!optional && [null, undefined].includes(params?.[segment])) {
				throw new Error(`Router error: [${segment}] parameter is required for route [${this.name}].`)
			}

			if (segments[segments.length - 1].name === segment && this.definition?.wheres?.[segment] === '.*') {
				return encodeURIComponent(params[segment] ?? '').replace(/%2F/g, '/')
			}

			if (this.definition?.wheres?.[segment] && !new RegExp(`^${optional ? `(${this.definition?.wheres?.[segment]})?` : this.definition?.wheres?.[segment]}$`).test(params[segment] ?? '')) {
				throw new Error(`Router error: [${segment}] parameter does not match required format [${this.definition?.wheres?.[segment]}] for route [${this.name}].`)
			}

			return encodeURIComponent(params[segment] ?? '')
		}).replace(/\/+$/, '')
	}
}
