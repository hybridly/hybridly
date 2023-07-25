import { setContext } from '../context'
import type { RoutingConfiguration } from './types'

export { route } from './route'
export { currentRouteMatches, getCurrentRouteName } from './current'

export function updateRoutingConfiguration(routing?: RoutingConfiguration) {
	if (!routing) {
		return
	}

	setContext({ routing })
}

export * from './types'
