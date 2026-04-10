export type HybridlyErrorCode =
	| 'HYB_NAVIGATION_CANCELLED'
	| 'HYB_ROUTING_NOT_INITIALIZED'
	| 'HYB_ROUTE_NOT_FOUND'
	| 'HYB_MISSING_ROUTE_PARAMETER'
	| 'HYB_INVALID_RESPONSE'

export type HybridlyErrorKind =
	| 'response.invalid'
	| 'navigation.cancelled'
	| 'routing.not-initialized'
	| 'routing.route-not-found'
	| 'routing.missing-parameter'

export class HybridlyError extends Error {
	public readonly kind: HybridlyErrorKind
	public readonly code: HybridlyErrorCode
	public readonly details?: Record<string, unknown>
	public readonly cause?: unknown
	public readonly isHybridlyError = true

	constructor(message: string, options: HybridlyErrorOptions) {
		super(message)
		this.name = 'HybridlyError'
		this.kind = options.kind
		this.code = options.code
		this.details = options.details
		this.cause = options.cause
	}
}

export class InvalidResponseError extends HybridlyError {
	constructor(options: Omit<HybridlyErrorOptions, 'kind' | 'code'> = {}) {
		super('The response was not a valid hybrid response.', {
			...options,
			kind: 'response.invalid',
			code: 'HYB_INVALID_RESPONSE',
		})

		this.name = 'InvalidResponseError'
	}
}

export class NavigationCancelledError extends HybridlyError {
	constructor(message = 'The navigation was cancelled.', options: Omit<HybridlyErrorOptions, 'kind' | 'code'> = {}) {
		super(message, {
			...options,
			kind: 'navigation.cancelled',
			code: 'HYB_NAVIGATION_CANCELLED',
		})

		this.name = 'NavigationCancelledError'
	}
}

export class RoutingNotInitialized extends HybridlyError {
	constructor(options: Omit<HybridlyErrorOptions, 'kind' | 'code'> = {}) {
		super('Routing is not initialized. Make sure the Vite plugin is enabled and that `php artisan route:list` returns no error.', {
			...options,
			kind: 'routing.not-initialized',
			code: 'HYB_ROUTING_NOT_INITIALIZED',
		})

		this.name = 'RoutingNotInitialized'
	}
}

export class RouteNotFound extends HybridlyError {
	constructor(name: string, options: Omit<HybridlyErrorOptions, 'kind' | 'code' | 'details'> = {}) {
		super(`Route [${name}] does not exist.`, {
			...options,
			kind: 'routing.route-not-found',
			code: 'HYB_ROUTE_NOT_FOUND',
			details: { name },
		})

		this.name = 'RouteNotFound'
	}
}

export class MissingRouteParameter extends HybridlyError {
	constructor(parameter: string, routeName: string, options: Omit<HybridlyErrorOptions, 'kind' | 'code' | 'details'> = {}) {
		super(`Parameter [${parameter}] is required for route [${routeName}].`, {
			...options,
			kind: 'routing.missing-parameter',
			code: 'HYB_MISSING_ROUTE_PARAMETER',
			details: {
				parameter,
				routeName,
			},
		})

		this.name = 'MissingRouteParameter'
	}
}

export function isHybridlyError(error: unknown): error is HybridlyError {
	return error instanceof Error && (error as Partial<HybridlyError>).isHybridlyError === true
}

export function isNavigationCancelledError(error: unknown): error is NavigationCancelledError {
	return isHybridlyError(error) && error.kind === 'navigation.cancelled'
}

export function isInvalidResponseError(error: unknown): error is InvalidResponseError {
	return isHybridlyError(error) && error.kind === 'response.invalid'
}

interface HybridlyErrorOptions {
	kind: HybridlyErrorKind
	code: HybridlyErrorCode
	details?: Record<string, unknown>
	cause?: unknown
}
