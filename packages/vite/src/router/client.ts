import type { RoutingConfiguration } from '@hybridly/core'
import { ROUTING_HMR_UPDATE_ROUTING, ROUTING_HMR_QUERY_UPDATE_ROUTING } from '../constants'

/**
* Injects the route collection into the client code.
* When HMR triggers, an event with the new routes is dispatched.
*/
export function getRouterClientCode(routing?: RoutingConfiguration) {
	return `
		if (typeof window !== 'undefined') {
			window.hybridly = {
				routing: ${JSON.stringify(routing)}
			}

			if (import.meta.hot) {
				import.meta.hot.on('${ROUTING_HMR_UPDATE_ROUTING}', (routing) => {
					window.dispatchEvent(new CustomEvent('hybridly:routing', { detail: routing }))
				})

				import.meta.hot.send('${ROUTING_HMR_QUERY_UPDATE_ROUTING}')
			}
		}
 `
}
