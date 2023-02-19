import path from 'node:path'
import type { Plugin } from 'vite'
import type { ResolvedHybridlyConfig } from 'hybridly'
import { loadHybridlyConfig } from 'hybridly'
import { CONFIG_PLUGIN_NAME, CONFIG_VIRTUAL_MODULE_ID, RESOLVED_CONFIG_VIRTUAL_MODULE_ID, ROUTING_VIRTUAL_MODULE_ID } from '../constants'
import type { ViteOptions } from '../types'

export default (options: ViteOptions, config: ResolvedHybridlyConfig): Plugin => {
	return {
		name: CONFIG_PLUGIN_NAME,
		enforce: 'pre',
		config() {
			return {
				resolve: {
					alias: {
						'@': path.join(process.cwd(), config.root),
						'#': path.join(process.cwd(), '.hybridly'),
						'~': path.join(process.cwd()),
					},
				},
			}
		},
		resolveId(id) {
			if (id === CONFIG_VIRTUAL_MODULE_ID) {
				return RESOLVED_CONFIG_VIRTUAL_MODULE_ID
			}
		},
		async load(id) {
			if (id === RESOLVED_CONFIG_VIRTUAL_MODULE_ID) {
				const config = await loadHybridlyConfig()

				return `
					import { initializeHybridly as init } from 'hybridly/vue'
					import '${ROUTING_VIRTUAL_MODULE_ID}'

					export function initializeHybridly(config) {
						return init({
							...${JSON.stringify(config)},
							components: import.meta.glob("${config.pagesGlob}", { eager: ${config.eager ?? true} }),
							...config,
						})
					}
				`
			}
		},
	}
}
