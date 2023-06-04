import path from 'node:path'
import type { Plugin } from 'vite'
import { CONFIG_PLUGIN_NAME, CONFIG_VIRTUAL_MODULE_ID, RESOLVED_CONFIG_VIRTUAL_MODULE_ID, ROUTING_VIRTUAL_MODULE_ID } from '../constants'
import type { ViteOptions, Configuration } from '../types'

export default (options: ViteOptions, config: Configuration): Plugin => {
	return {
		name: CONFIG_PLUGIN_NAME,
		enforce: 'pre',
		config() {
			return {
				resolve: {
					alias: {
						'@': path.join(process.cwd(), config.architecture.root),
						'#': path.join(process.cwd(), '.hybridly'),
						'~': path.join(process.cwd()),
					},
				},
			}
		},
		configureServer(server) {
			const reloadServer = (file: string) => {
				if (!file.endsWith('config/hybridly.php')) {
					return
				}

				server.config.logger.info('Hybridly configuration file was changed: forcing a server restart.', {
					clear: server.config.clearScreen,
					timestamp: true,
				})

				server?.restart()
			}

			server.watcher.on('add', reloadServer)
			server.watcher.on('change', reloadServer)
			server.watcher.on('unlink', reloadServer)
		},
		resolveId(id) {
			if (id === CONFIG_VIRTUAL_MODULE_ID) {
				return RESOLVED_CONFIG_VIRTUAL_MODULE_ID
			}
		},
		async load(id) {
			if (id === RESOLVED_CONFIG_VIRTUAL_MODULE_ID) {
				const paths = config.components.views
					.map(({ path }) => path.replace(process.cwd(), '~'))
					.map((path) => `"${path}"`).join(',')

				return `
					import { initializeHybridly as init } from 'hybridly/vue'
					import '${ROUTING_VIRTUAL_MODULE_ID}'

					export function initializeHybridly(config) {
						return init({
							...${JSON.stringify(config)},
							components: {
								views: ${JSON.stringify(config.components.views)},
								layouts: ${JSON.stringify(config.components.layouts)},
								imported: import.meta.glob([${paths}], { eager: ${config.components.eager ?? true} }),
							},
							...config,
						})
					}
				`
			}
		},
	}
}
