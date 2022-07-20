import type { Method } from '@monolikit/core'

export interface RouterConfiguration {
	url: string
	port?: number
	defaults: Record<string, any>
}

export interface RouteDefinition {
	uri: string
	methods: Method[]
	bindings: Record<string, string>
	domain?: string
	wheres?: Record<string, string>
}

export interface RouteCollection extends RouterConfiguration {
	routes: Record<string, RouteDefinition>
}

export interface GlobalRouteCollection extends RouteCollection {}

export type RouteName = keyof GlobalRouteCollection['routes']
export type RouteParameters<T extends RouteName> = Record<keyof GlobalRouteCollection['routes'][T]['bindings'], any> & Record<string, any>
