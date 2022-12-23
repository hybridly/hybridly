import { stringify } from 'qs'
import { getInternalRouterContext } from '../context'
import { Route } from './route'
import type { RouteName, RouteParameters, RoutingConfiguration } from './types'

/**
 * A collection of Laravel routes. This class constitutes Ziggy's main API.
 */
export class Router<T extends RouteName> extends String {
	public route: Route
	private parameters!: RouteParameters<T>
	private routing: RoutingConfiguration

	constructor(
		name: T,
		parameters?: RouteParameters<T>,
		absolute: boolean = true,
	) {
		super()
		const context = getInternalRouterContext()
		this.route = new Route(name, absolute)
		this.routing = context.routing!
		this.setParameters(parameters)
	}

	/**
	 * Gets the compiled URL string for the current route and parameters.
	 *
	 * @example
	 * // with 'posts.show' route 'posts/{post}'
	 * (new Router('posts.show', 1)).toString(); // 'https://ziggy.dev/posts/1'
	 */
	toString(): string {
		// Get parameters that don't correspond to any route segments to append them to the query
		const unhandled = Object.keys(this.parameters)
			.filter((key) => !this.route.parameterSegments.some(({ name }) => name === key))
			.filter((key) => key !== '_query')
			.reduce((result, current) => ({ ...result, [current]: this.parameters[current] }), {})

		return this.route.compile(this.parameters) + stringify({ ...unhandled, ...this.parameters._query }, {
			addQueryPrefix: true,
			arrayFormat: 'indices',
			encodeValuesOnly: true,
			skipNulls: true,
			encoder: (value, encoder) => typeof value === 'boolean'
				? Number(value).toString()
				: encoder(value),
		})
	}

	/**
	 * Checks whether the given route exists.
	 */
	static has(name: string): boolean {
		try {
			Route.getDefinition(name)
			return true
		} catch {
			return false
		}
	}

	/**
	 * Parse Laravel-style route parameters of any type into a normalized object.
	 *
	 * @example
	 * // with route parameter names 'event' and 'venue'
	 * _parse(1); // { event: 1 }
	 * _parse({ event: 2, venue: 3 }); // { event: 2, venue: 3 }
	 * _parse(['Taylor', 'Matt']); // { event: 'Taylor', venue: 'Matt' }
	 * _parse([4, { uuid: 56789, name: 'Grand Canyon' }]); // { event: 4, venue: 56789 }
	 */
	private setParameters(parameters?: RouteParameters<T>) {
		this.parameters = parameters ?? {}

		// If `params` is a string or integer, wrap it in an array
		this.parameters = ['string', 'number'].includes(typeof this.parameters) ? [this.parameters] : this.parameters

		// Separate segments with and without defaults, and fill in the default values
		const segments = this.route.parameterSegments.filter(({ name }) => !this.routing.defaults[name])

		if (Array.isArray(this.parameters)) {
			// If the parameters are an array they have to be in order, so we can transform them into
			// an object by keying them with the template segment names in the order they appear
			this.parameters = this.parameters.reduce((result, current, i) => segments[i]
				? ({ ...result, [segments[i].name]: current })
				: typeof current === 'object'
					? ({ ...result, ...current })
					: ({ ...result, [current]: '' }), {})
		} else if (
			segments.length === 1
            && !this.parameters[segments[0].name]
            && (Reflect.has(this.parameters, Object.values(this.route.definition.bindings)[0]) || Reflect.has(this.parameters, 'id'))
		) {
			// If there is only one template segment and `this.parameters` is an object, that object is
			// ambiguous—it could contain the parameter key and value, or it could be an object
			// representing just the value (e.g. a model); we can inspect it to find out, and
			// if it's just the parameter value, we can wrap it in an object with its key
			this.parameters = { [segments[0].name]: this.parameters }
		}

		this.parameters = {
			...this.getDefaults(),
			...this.substituteBindings(),
		}
	}

	/**
	 * Populates default parameters.
	 */
	private getDefaults() {
		return this.route.parameterSegments
			.filter(({ name }) => this.routing.defaults[name])
			.reduce((result, { name }) => ({ ...result, [name]: this.routing.defaults[name] }), {})
	}

	/**
     * Substitute Laravel route model bindings in the given parameters.
     *
     * @example
     * _substituteBindings({ post: { id: 4, slug: 'hello-world', title: 'Hello, world!' } }, { bindings: { post: 'slug' } }); // { post: 'hello-world' }
     */
	private substituteBindings() {
		return Object.entries(this.parameters).reduce((result, [key, value]) => {
			// If the value isn't an object, or if the key isn't a named route parameter,
			// there's nothing to substitute so we return it as-is
			if (!value || typeof value !== 'object' || Array.isArray(value) || !this.route.parameterSegments.some(({ name }) => name === key)) {
				return { ...result, [key]: value }
			}

			if (!Reflect.has(value, this.route.definition.bindings[key])) {
				// As a fallback, we still accept an 'id' key not explicitly registered as a binding
				if (Reflect.has(value, 'id')) {
					this.route.definition.bindings[key] = 'id'
				} else {
					throw new Error(`Router error: object passed as [${key}] parameter is missing route model binding key [${this.route.definition.bindings?.[key]}].`)
				}
			}

			return { ...result, [key]: value[this.route.definition.bindings[key]] }
		}, {})
	}

	valueOf() {
		return this.toString()
	}
}
