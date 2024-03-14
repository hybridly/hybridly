import path from 'node:path'
import { type Plugin } from 'vite'
import type { DynamicConfiguration } from '@hybridly/core'
import { CONFIG_PLUGIN_NAME, CONFIG_VIRTUAL_MODULE_ID, RESOLVED_CONFIG_VIRTUAL_MODULE_ID } from '../constants'
import type { ResolvedOptions } from '../types'
import { generateRouteDefinitionFile, generateLaravelIdeaHelper, generateTsConfig } from '../typegen'
import { loadConfiguration } from './load'
import { getClientCode } from './client'

export default (options: ResolvedOptions, config: DynamicConfiguration): Plugin => {
	generateTsConfig(options, config)
	generateLaravelIdeaHelper(options, config)
	generateRouteDefinitionFile(options, config)

	return {
		name: CONFIG_PLUGIN_NAME,
		enforce: 'pre',
		config() {
			return {
				resolve: {
					alias: {
						'@': path.resolve(options.laravelPath, config.architecture.root_directory),
						'#': path.resolve(options.laravelPath, '.hybridly'),
						'~': options.basePath,
					},
				},
				envDir: options.laravelPath,
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

				// When routing changes, write route definitions
				// to the disk and force-reload the dev server
				// Check for controller changes as well to support spatie/laravel-route-attributes
				// TODO make this configurable
				if (/routes\/.*\.php$/.test(file) || /app\/Http\/Controllers\/.*\.php$/.test(file) || /routes\.php$/.test(file)) {
					return await forceRestart('Routing changed')
				}

				// Force-reload the server when the routing or components change
				if (/.*\.vue$/.test(file)) {
					const updatedConfig = await loadConfiguration(options.laravelPath, options.basePath)
					const viewsOrLayoutsChanged = didViewsOrLayoutsChange(updatedConfig, config)

					if (viewsOrLayoutsChanged) {
						return await forceRestart('View or layout changed')
					}
				}
			}

			if (options.laravelPath !== options.basePath) {
				server.watcher.add([
					path.resolve(options.laravelPath, 'config/hybridly.php'),
					path.resolve(options.laravelPath, 'routes'),
					path.resolve(options.laravelPath, 'app', 'Http'),
				])
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
