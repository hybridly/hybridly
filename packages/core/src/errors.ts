import type { AxiosResponse } from 'axios'

export class NotAHybridResponseError extends Error {
	constructor(public response: AxiosResponse) {
		super()
	}
}

export class NavigationCancelledError extends Error {}

export class RoutingNotInitialized extends Error {
	constructor() {
		super('Routing is not initialized. Make sure the Vite plugin is enabled and that `php artisan route:list` returns no error.')
	}
}

export class RouteNotFound extends Error {
	constructor(name: string) {
		super(`Route [${name}] does not exist.`)
	}
}

export class MissingRouteParameter extends Error {
	constructor(parameter: string, routeName: string) {
		super(`Parameter [${parameter}] is required for route [${routeName}].`)
	}
}
