import type { Method } from '../router/types'

export interface RoutingConfiguration {
	url: string
	port?: number
	defaults: Record<string, any>
	routes: Record<string, RouteDefinition>
}

export interface RouteDefinition {
	uri: string
	method: Method[]
	bindings: Record<string, string>
	domain?: string
	wheres?: Record<string, string>
}

export interface GlobalRouteCollection extends RoutingConfiguration {}

export type RouteName = keyof GlobalRouteCollection['routes']
export type RouteParameters<T extends RouteName> = Record<keyof GlobalRouteCollection['routes'][T]['bindings'], any> & Record<string, any>
