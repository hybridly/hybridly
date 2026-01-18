import type { RoutingConfiguration } from '../routing'

export interface DynamicConfiguration {
	versions: {
		composer: string
		npm: string
		latest: string
		is_latest: boolean
	}
	architecture: {
		root_directory: string
		application_main_path: string
	}
	components: {
		eager?: boolean
		views: Component[]
		layouts: Component[]
	}
	routing: RoutingConfiguration
}

interface Component {
	path: string
	identifier: string
	namespace: string
}
