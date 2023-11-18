import type { RoutingConfiguration } from '../routing'

export interface DynamicConfiguration {
	versions: {
		composer: string
		npm: string
		latest: string
		is_latest: boolean
	}
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
}

interface Component {
	path: string
	identifier: string
	namespace: string
}
