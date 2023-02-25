import type { Plugin, ViteDevServer } from 'vite'
import type { RoutingConfiguration } from '@hybridly/core'
import type { HybridlyConfig } from '@hybridly/config'
import type { ViteOptions, RouterOptions } from '../types'
import { RESOLVED_ROUTING_VIRTUAL_MODULE_ID, ROUTING_HMR_QUERY_UPDATE_ROUTING, ROUTING_HMR_UPDATE_ROUTING, ROUTING_PLUGIN_NAME, ROUTING_VIRTUAL_MODULE_ID } from '../constants'
import { debug } from '../utils'
import { getRouterClientCode } from './client'
import { write } from './typegen'
import { fetchRoutingFromArtisan } from './routes'

export default (options: ViteOptions, config: HybridlyConfig): Plugin => {
	const resolved: Required<RouterOptions> = {
		php: 'php',
		dts: '.hybridly/routes.d.ts',
		watch: [
			/routes\/.*\.php/,
		],
		...options,
	}

	let routingBeforeUpdate: RoutingConfiguration
	async function sendRoutingUpdate(server: ViteDevServer, force: boolean = false) {
		const routing = await fetchRoutingFromArtisan(resolved) ?? routingBeforeUpdate

		// When routes changes, we want to update the routes by triggering
		// a custom HMR event with the new updated route collection.
		if (force || JSON.stringify(routing) !== JSON.stringify(routingBeforeUpdate)) {
			debug.router('Updating routes via HMR:', routing)

			server.ws.send({
				type: 'custom',
				event: ROUTING_HMR_UPDATE_ROUTING,
				data: routing,
			})

			write(resolved)
			routingBeforeUpdate = routing
		}
	}

	return {
		name: ROUTING_PLUGIN_NAME,
		configureServer(server) {
			write(resolved)

			server.ws.on(ROUTING_HMR_QUERY_UPDATE_ROUTING, () => {
				sendRoutingUpdate(server, true)
			})

			server.watcher.on('change', async(path) => {
				if (!resolved.watch.some((regex) => regex.test(path))) {
					return
				}

				sendRoutingUpdate(server)
			})
		},
		resolveId(id) {
			if (id === ROUTING_VIRTUAL_MODULE_ID) {
				return RESOLVED_ROUTING_VIRTUAL_MODULE_ID
			}
		},
		async load(id) {
			if (id === RESOLVED_ROUTING_VIRTUAL_MODULE_ID) {
				const routing = await fetchRoutingFromArtisan(resolved)
				return getRouterClientCode(routing)
			}
		},
		async handleHotUpdate(ctx) {
			// Prevent triggering a page reload when the definition file is rewritten
			if (typeof resolved.dts === 'string' && ctx.file.endsWith(resolved.dts)) {
				return []
			}
		},
		transform() {
			write(resolved)
		},
	}
}
