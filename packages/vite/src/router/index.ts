import type { Plugin } from 'vite'
import type { RouteCollection } from '@monolikit/vue'
import type { RouterOptions } from '../types'
import { RESOLVED_ROUTER_VIRTUAL_MODULE_ID, ROUTER_HMR_UPDATE_ROUTE, ROUTER_PLUGIN_NAME, ROUTER_VIRTUAL_MODULE_ID } from '../constants'
import { debug } from '../utils'
import { getClientCode } from './client'
import { write } from './typegen'
import { fetchRoutesFromArtisan } from './routes'

/**
 * A basic Vite plugin that adds a <template layout="name"> syntax to Vite SFCs.
 * It must be used before the Vue plugin.
 */
export default (options: RouterOptions = {}): Plugin => {
	const resolved: Required<RouterOptions> = {
		php: 'php',
		dts: 'resources/types/routes.d.ts',
		watch: [
			/routes\/.*\.php/,
		],
		...options,
	}

	let previousRoutes: RouteCollection

	return {
		name: ROUTER_PLUGIN_NAME,
		configureServer() {
			write(resolved)
		},
		resolveId(id) {
			if (id === ROUTER_VIRTUAL_MODULE_ID) {
				return RESOLVED_ROUTER_VIRTUAL_MODULE_ID
			}
		},
		async load(id) {
			if (id === RESOLVED_ROUTER_VIRTUAL_MODULE_ID) {
				const routes = await fetchRoutesFromArtisan(resolved)
				return getClientCode(routes)
			}
		},
		async handleHotUpdate(ctx) {
			// Prevent triggering a page reload when the definition file is rewritten
			if (typeof resolved.dts === 'string' && ctx.file.endsWith(resolved.dts)) {
				return []
			}

			if (!resolved.watch.some((regex) => regex.test(ctx.file))) {
				return
			}

			const routes = await fetchRoutesFromArtisan(resolved)

			// When routes changes, we want to update the routes by triggering
			// a custom HMR event with the new updated route collection.
			if (JSON.stringify(routes) !== JSON.stringify(previousRoutes)) {
				debug.router('Updating routes via HMR:', routes)

				ctx.server.ws.send({
					type: 'custom',
					event: ROUTER_HMR_UPDATE_ROUTE,
					data: routes,
				})

				write(resolved)
				previousRoutes = routes
			}
		},
		transform() {
			write(resolved)
		},
	}
}
