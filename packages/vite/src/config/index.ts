import type { DynamicConfiguration } from '@hybridly/core'
import path from 'node:path'
import { type Plugin } from 'vite'
import { CONFIG_PLUGIN_NAME, CONFIG_VIRTUAL_MODULE_ID, RESOLVED_CONFIG_VIRTUAL_MODULE_ID } from '../constants'
import { generateAppTsConfig, generateLaravelIdeaHelper, generateNodeTsConfig, generateVueExtensionFile } from '../typegen'
import type { ViteOptions } from '../types'
import { getClientCode } from './client'
import { loadConfiguration } from './load'

export default (options: ViteOptions, config: DynamicConfiguration): Plugin => {
	generateAppTsConfig(options, config)
	generateNodeTsConfig(options, config)
	generateLaravelIdeaHelper(config)
	generateVueExtensionFile()

	return {
		name: CONFIG_PLUGIN_NAME,
		enforce: 'pre',
		config() {
			return {
				resolve: {
					alias: {
						'@': path.join(process.cwd(), config.architecture.root_directory),
						'#': path.join(process.cwd(), '.hybridly'),
						'~': path.join(process.cwd()),
					},
				},
			}
		},
		configureServer(server) {
			let restarting = false

			async function forceRestart(message: string) {
				if (restarting) {
					return
				}

				restarting = true
				server.config.logger.info(`${message}: forcing a server restart.`, {
					clear: server.config.clearScreen,
					timestamp: true,
				})

				return await server?.restart()
			}

			async function handleFileChange(file: string) {
				// Force-reload the server when the config changes
				if (file.endsWith('config/hybridly.php')) {
					return await forceRestart('Configuration file changed')
				}

				// Force-reload the server when components change
				if (/.*\.vue$/.test(file)) {
					loadConfiguration()
						.then((updatedConfig) => {
							if (didViewsOrLayoutsChange(updatedConfig, config)) {
								forceRestart('View or layout changed')
							}
						})
						.catch()
				}
			}

			server.watcher.on('add', handleFileChange)
			server.watcher.on('change', handleFileChange)
			server.watcher.on('unlink', handleFileChange)
		},
		resolveId(id) {
			if (id === CONFIG_VIRTUAL_MODULE_ID) {
				return RESOLVED_CONFIG_VIRTUAL_MODULE_ID
			}
		},
		async load(id) {
			if (id === RESOLVED_CONFIG_VIRTUAL_MODULE_ID) {
				return getClientCode(config)
			}
		},
		// Denies HMR for `.hybridly` content, it causes unwanted reloads
		async handleHotUpdate(ctx) {
			if (ctx.file.includes('.hybridly')) {
				return []
			}
		},
	}
}

function didViewsOrLayoutsChange(updatedConfig: DynamicConfiguration, previousConfig?: DynamicConfiguration) {
	if (!previousConfig) {
		return false
	}

	return JSON.stringify(updatedConfig.components.views) !== JSON.stringify(previousConfig.components.views)
		|| JSON.stringify(updatedConfig.components.layouts) !== JSON.stringify(previousConfig.components.layouts)
}
