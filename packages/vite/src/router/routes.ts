import type { ViteOptions } from '../types'
import { loadConfiguration } from '../config/load'
import { write } from './typegen'

export async function fetchRoutingFromArtisan(options: ViteOptions) {
	try {
		const { routes } = await loadConfiguration(options)
		write(options, routes)

		return routes
	} catch {}
}
