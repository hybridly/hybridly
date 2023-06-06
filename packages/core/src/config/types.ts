import type { RoutingConfiguration } from '../routing'

export interface DynamicConfiguration {
	architecture: {
		root: string
	}
	components: {
		eager?: boolean
		directories: string[]
		views: Component[]
		layouts: Component[]
		components: Component[]
	}
	routing: RoutingConfiguration
	ssr: boolean
}

interface Component {
	path: string
	identifier: string
	namespace: string
}
