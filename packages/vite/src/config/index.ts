import path from 'node:path'
import { type Plugin } from 'vite'
import type { DynamicConfiguration } from '@hybridly/core'
import colors from 'picocolors'
import { CONFIG_PLUGIN_NAME, CONFIG_VIRTUAL_MODULE_ID, RESOLVED_CONFIG_VIRTUAL_MODULE_ID } from '../constants'
import type { ViteOptions } from '../types'
import { generateRouteDefinitionFile, generateLaravelIdeaHelper, generateTsConfig } from '../typegen'
import { loadConfiguration } from './load'
import { getClientCode } from './client'

export default (options: ViteOptions, config: DynamicConfiguration): Plugin => {
	generateTsConfig(options, config)
	generateLaravelIdeaHelper(config)
	generateRouteDefinitionFile(options, config)

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
				if (/routes\/.*\.php/.test(file) || /routes\.php/.test(file)) {
					return await forceRestart('Routing changed')
				}

				// Force-reload the server when the routing or components change
				if (/.*\.vue$/.test(file)) {
					const updatedConfig = await loadConfiguration(options)
					const viewsOrLayoutsChanged = didViewsOrLayoutsChange(updatedConfig, config)

					if (viewsOrLayoutsChanged) {
						return await forceRestart('View or layout changed')
					}
				}
			}

			server.watcher.on('add', handleFileChange)
			server.watcher.on('change', handleFileChange)
			server.watcher.on('unlink', handleFileChange)

			server.httpServer?.once('listening', () => {
				if (!config.versions) {
					return
				}

				let registered = `${colors.bold(config.components.views.length)} ${colors.dim('views')}, `
				registered += `${colors.bold(config.components.components.length)} ${colors.dim('components')}, `
				registered += `${colors.bold(config.components.layouts.length)} ${colors.dim('layouts')}, `
				registered += `${colors.bold(config.components.directories.length)} ${colors.dim('directories')}`

				const eagerLoading = config.components.eager ? colors.dim('enabled') : colors.dim('disabled')
				const latest = config.versions.is_latest ? '' : `${colors.yellow(colors.bold(config.versions.latest))} ${colors.yellow('available')}`

				let version = `${colors.yellow(`v${config.versions.composer}`)} ${colors.dim('(composer)')}, `
				version += `${colors.yellow(`v${config.versions.npm}`)} ${colors.dim('(npm)')}`
				version += ` — ${colors.yellow('this may lead to undefined behavior')}`

				setTimeout(() => {
					server.config.logger.info(`\n  ${colors.magenta(`${colors.bold('HYBRIDLY')} v${config.versions.composer}`)}  ${latest}`)
					server.config.logger.info('')
					server.config.logger.info(`  ${colors.green('➜')}  ${colors.bold('URL')}: ${colors.cyan(config.routing.url)}`)
					server.config.logger.info(`  ${colors.green('➜')}  ${colors.bold('Registered')}: ${registered}`)
					server.config.logger.info(`  ${colors.green('➜')}  ${colors.bold('Eager loading')}: ${eagerLoading}`)

					if (config.versions.composer !== config.versions.npm) {
						server.config.logger.info(`  ${colors.yellow('➜')}  ${colors.bold('Version mismatch')}: ${version}`)
					}
				}, 120)
			})
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
