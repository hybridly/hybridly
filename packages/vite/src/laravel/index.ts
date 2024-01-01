import fs from 'node:fs'
import type { AddressInfo } from 'node:net'
import path from 'node:path'
import colors from 'picocolors'
import type { Plugin, UserConfig, ResolvedConfig } from 'vite'
import { loadEnv } from 'vite'
import type { DynamicConfiguration } from 'hybridly'
import type { InputOption } from 'rollup'
import type { ViteOptions } from '../types'
import { isIpv6 } from './utils'
import { resolveDevelopmentEnvironmentServerConfig, resolveEnvironmentServerConfig } from './dev-env'

type DevServerUrl = `${'http' | 'https'}://${string}:${number}`
let exitHandlersBound = false

export default function laravel(options: ViteOptions, hybridlyConfig: DynamicConfiguration): Plugin {
	let viteDevServerUrl: DevServerUrl
	let resolvedConfig: ResolvedConfig
	let userConfig: UserConfig

	const publicDirectory = 'public'
	const buildDirectory = 'build'
	const hotFile = path.join(publicDirectory, 'hot')

	return {
		name: 'hybridly:laravel',
		enforce: 'post',
		config: (config, { command, mode }) => {
			userConfig = config
			const ssr = !!userConfig.build?.ssr
			const env = loadEnv(mode, userConfig.envDir || process.cwd(), '')
			const assetUrl = env.ASSET_URL ?? ''
			const base = `${assetUrl + (!assetUrl.endsWith('/') ? '/' : '') + buildDirectory}/`

			// TODO
			const serverConfig = command === 'serve'
				? (resolveEnvironmentServerConfig(env) ?? resolveDevelopmentEnvironmentServerConfig())
				: undefined

			ensureCommandShouldRunInEnvironment(command, env)

			return {
				base: userConfig.base ?? (command === 'build' ? base : ''),
				publicDir: userConfig.publicDir ?? false,
				build: {
					manifest: ssr === true ? false : (userConfig.build?.manifest ?? 'manifest.json'),
					outDir: userConfig.build?.outDir ?? path.join(publicDirectory, buildDirectory),
					rollupOptions: {
						input: resolveInput(config, hybridlyConfig, ssr),
					},
					assetsInlineLimit: userConfig.build?.assetsInlineLimit ?? 0,
				},
				server: {
					origin: userConfig.server?.origin ?? '__laravel_vite_placeholder__',
					...(process.env.LARAVEL_SAIL ? {
						host: userConfig.server?.host ?? '0.0.0.0',
						port: userConfig.server?.port ?? (env.VITE_PORT ? parseInt(env.VITE_PORT) : 5173),
						strictPort: userConfig.server?.strictPort ?? true,
					} : undefined),
					...(serverConfig ? {
						host: userConfig.server?.host ?? serverConfig.host,
						hmr: userConfig.server?.hmr === false ? false : {
							...serverConfig.hmr,
							...(userConfig.server?.hmr === true ? {} : userConfig.server?.hmr),
						},
						https: userConfig.server?.https ?? serverConfig.https,
					} : undefined),
				},
			}
		},
		configResolved(config) {
			resolvedConfig = config
		},
		transform(code) {
			if (resolvedConfig.command === 'serve') {
				return code.replace(/__laravel_vite_placeholder__/g, viteDevServerUrl)
			}
		},
		configureServer(server) {
			const envDir = resolvedConfig.envDir || process.cwd()
			const appUrl = loadEnv(resolvedConfig.mode, envDir, 'APP_URL').APP_URL ?? 'undefined'

			server.httpServer?.once('listening', () => {
				const address = server.httpServer?.address()
				const isAddressInfo = (x: string | AddressInfo | null | undefined): x is AddressInfo => typeof x === 'object'

				if (isAddressInfo(address)) {
					viteDevServerUrl = resolveDevServerUrl(address, server.config, userConfig)
					fs.writeFileSync(hotFile, viteDevServerUrl)

					if (!hybridlyConfig.versions) {
						return
					}

					let registered = `${colors.bold(hybridlyConfig.components.views.length)} ${colors.dim('views')}, `
					registered += `${colors.bold(hybridlyConfig.components.components.length)} ${colors.dim('components')}, `
					registered += `${colors.bold(hybridlyConfig.components.layouts.length)} ${colors.dim('layouts')}, `
					registered += `${colors.bold(hybridlyConfig.components.directories.length)} ${colors.dim('directories')}`

					const latest = hybridlyConfig.versions.is_latest ? '' : colors.dim(`(${colors.yellow(`v${hybridlyConfig.versions.latest} is available`)})`)

					let version = `${colors.yellow(`v${hybridlyConfig.versions.composer}`)} ${colors.dim('(composer)')}, `
					version += `${colors.yellow(`v${hybridlyConfig.versions.npm}`)} ${colors.dim('(npm)')}`
					version += ` — ${colors.yellow('this may lead to undefined behavior')}`

					setTimeout(() => {
						server.config.logger.info(`\n  ${colors.magenta(`${colors.bold('HYBRIDLY')} v${hybridlyConfig.versions.composer}`)}  ${latest}`)
						server.config.logger.info('')
						server.config.logger.info(`  ${colors.green('➜')}  ${colors.bold('URL')}: ${colors.cyan(hybridlyConfig.routing.url)}`)
						server.config.logger.info(`  ${colors.green('➜')}  ${colors.bold('Registered')}: ${registered}`)

						if (hybridlyConfig.versions.composer !== hybridlyConfig.versions.npm) {
							server.config.logger.info(`  ${colors.yellow('➜')}  ${colors.bold('Version mismatch')}: ${version}`)
						}
					}, 100)
				}
			})

			if (!exitHandlersBound) {
				function clean() {
					if (fs.existsSync(hotFile)) {
						fs.rmSync(hotFile)
					}
				}

				process.on('exit', clean)
				process.on('SIGINT', () => process.exit())
				process.on('SIGTERM', () => process.exit())
				process.on('SIGHUP', () => process.exit())

				exitHandlersBound = true
			}

			return () => server.middlewares.use((req, res, next) => {
				if (req.url === '/index.html') {
					res.writeHead(302, { Location: appUrl })
					res.end()
				}

				next()
			})
		},
	}
}

/**
 * Validates the command can run in the given environment.
 */
function ensureCommandShouldRunInEnvironment(command: 'build' | 'serve', env: Record<string, string>): void {
	if (command === 'build' || env.LARAVEL_BYPASS_ENV_CHECK === '1') {
		return
	}

	if (typeof env.LARAVEL_VAPOR !== 'undefined') {
		throw new TypeError('You should not run the Vite HMR server on Vapor. You should build your assets for production instead. To disable this ENV check you may set LARAVEL_BYPASS_ENV_CHECK=1')
	}

	if (typeof env.LARAVEL_FORGE !== 'undefined') {
		throw new TypeError('You should not run the Vite HMR server in your Forge deployment script. You should build your assets for production instead. To disable this ENV check you may set LARAVEL_BYPASS_ENV_CHECK=1')
	}

	if (typeof env.LARAVEL_ENVOYER !== 'undefined') {
		throw new TypeError('You should not run the Vite HMR server in your Envoyer hook. You should build your assets for production instead. To disable this ENV check you may set LARAVEL_BYPASS_ENV_CHECK=1')
	}

	if (typeof env.CI !== 'undefined') {
		throw new TypeError('You should not run the Vite HMR server in CI environments. You should build your assets for production instead. To disable this ENV check you may set LARAVEL_BYPASS_ENV_CHECK=1')
	}
}

/**
 * Resolves input files.
 */
function resolveInput(userConfig: UserConfig, hybridlyConfig: DynamicConfiguration, ssr: boolean): InputOption | string | undefined {
	// TODO: SSR support
	// if (ssr) {
	// 	return config.ssr
	// }

	return userConfig.build?.rollupOptions?.input
		?? hybridlyConfig.architecture.application_main_path
}

/**
 * Resolves the dev server URL from the server address and configuration.
 */
function resolveDevServerUrl(address: AddressInfo, config: ResolvedConfig, userConfig: UserConfig): DevServerUrl {
	const configHmrProtocol = typeof config.server.hmr === 'object' ? config.server.hmr.protocol : null
	const clientProtocol = configHmrProtocol ? (configHmrProtocol === 'wss' ? 'https' : 'http') : null
	const serverProtocol = config.server.https ? 'https' : 'http'
	const protocol = clientProtocol ?? serverProtocol

	const configHmrHost = typeof config.server.hmr === 'object' ? config.server.hmr.host : null
	const configHost = typeof config.server.host === 'string' ? config.server.host : null
	const sailHost = process.env.LARAVEL_SAIL && !userConfig.server?.host ? 'localhost' : null
	const serverAddress = isIpv6(address) ? `[${address.address}]` : address.address
	const host = configHmrHost ?? sailHost ?? configHost ?? serverAddress

	const configHmrClientPort = typeof config.server.hmr === 'object' ? config.server.hmr.clientPort : null
	const port = configHmrClientPort ?? address.port

	return `${protocol}://${host}:${port}`
}
