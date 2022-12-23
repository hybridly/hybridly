import type { RoutingConfiguration } from '@hybridly/core'

declare global {
	interface Window {
		hybridly: {
			routing?: RoutingConfiguration
		}
	}
}

export {}
