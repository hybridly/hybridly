import path from 'node:path'
import fs from 'node:fs'
import os from 'node:os'

/**
 * Resolves the path to the Herd or Valet configuration directory.
 */
export function determineDevelopmentEnvironmentConfigPath(): string {
	const herdConfigPath = path.resolve(os.homedir(), 'Library', 'Application Support', 'Herd', 'config', 'valet')

	if (fs.existsSync(herdConfigPath)) {
		return herdConfigPath
	}

	return path.resolve(os.homedir(), '.config', 'valet')
}

/**
 * Resolves the Herd or Valet host for the current directory.
 */
export function resolveDevelopmentEnvironmentHost(configPath: string): string | undefined {
	const configFile = path.resolve(configPath, 'config.json')

	if (!fs.existsSync(configFile)) {
		return
	}

	const config: { tld: string } = JSON.parse(fs.readFileSync(configFile, 'utf-8'))

	return `${path.basename(process.cwd())}.${config.tld}`
}

/**
 * Resolves the Herd or Valet server config for the given host.
 */
export function resolveDevelopmentEnvironmentServerConfig(): {
	hmr?: { host: string }
	host?: string
	https?: { cert: Buffer; key: Buffer }
} | undefined {
	const configPath = determineDevelopmentEnvironmentConfigPath()
	const host = resolveDevelopmentEnvironmentHost(configPath)

	if (!host) {
		return
	}

	const keyPath = path.resolve(configPath, 'Certificates', `${host}.key`)
	const certPath = path.resolve(configPath, 'Certificates', `${host}.crt`)

	if (!fs.existsSync(keyPath) || !fs.existsSync(certPath)) {
		return
	}

	return {
		hmr: { host },
		host,
		https: {
			key: fs.readFileSync(keyPath),
			cert: fs.readFileSync(certPath),
		},
	}
}

/**
 * Resolves the server config from the environment.
 */
export function resolveEnvironmentServerConfig(env: Record<string, string>): {
	hmr?: { host: string }
	host?: string
	https?: { cert: Buffer; key: Buffer }
} | undefined {
	if (!env.VITE_DEV_SERVER_KEY && !env.VITE_DEV_SERVER_CERT) {
		return
	}

	if (!fs.existsSync(env.VITE_DEV_SERVER_KEY) || !fs.existsSync(env.VITE_DEV_SERVER_CERT)) {
		throw new Error(`Unable to find the certificate files specified in your environment. Ensure you have correctly configured VITE_DEV_SERVER_KEY: [${env.VITE_DEV_SERVER_KEY}] and VITE_DEV_SERVER_CERT: [${env.VITE_DEV_SERVER_CERT}].`)
	}

	const host = resolveHostFromEnv(env)

	return {
		hmr: { host },
		host,
		https: {
			key: fs.readFileSync(env.VITE_DEV_SERVER_KEY),
			cert: fs.readFileSync(env.VITE_DEV_SERVER_CERT),
		},
	}
}

/**
 * Resolve the host name from the environment.
 */
function resolveHostFromEnv(env: Record<string, string>): string {
	if (env.VITE_DEV_SERVER_KEY) {
		return env.VITE_DEV_SERVER_KEY
	}

	try {
		return new URL(env.APP_URL).host
	} catch {
		throw new Error(`Unable to determine the host from the environment's APP_URL: [${env.APP_URL}].`)
	}
}
