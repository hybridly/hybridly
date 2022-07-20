import type { RouteCollection } from './routes'

declare global {
	interface Window {
		monolikit: {
			routes?: RouteCollection
		}
	}
}

export {}
