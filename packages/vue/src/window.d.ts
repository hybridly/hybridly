import type { RouteCollection } from './routes'

declare global {
	interface Window {
		hybridly: {
			routes?: RouteCollection
		}
	}
}

export {}
