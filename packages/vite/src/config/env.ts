import fs from 'node:fs/promises'
import { loadEnv } from 'vite'

let phpExecutable: string[]
let devEnvironment: 'ddev' | 'lando' | 'native'

/**
 * Gets all environment variables, including `.env` ones.
 */
export function getEnv(laravelPath: string, prefixes: string | Array<string> = '') {
	return { ...process.env, ...loadEnv('mock', laravelPath, prefixes) }
}

export async function getPhpExecutable(laravelPath: string): Promise<string[]> {
	if (phpExecutable) {
		return phpExecutable
	}

	const env = getEnv(laravelPath, 'PHP_EXECUTABLE_PATH')
	const php = (env.PHP_EXECUTABLE_PATH ?? 'php').split(' ')

	if (!env.PHP_EXECUTABLE_PATH) {
		const devEnvironment = await determineDevEnvironment()

		if (devEnvironment === 'ddev' || devEnvironment === 'lando') {
			php.unshift(devEnvironment)
		}
	}

	return phpExecutable = php
}

export async function determineDevEnvironment() {
	if (devEnvironment) {
		return devEnvironment
	}

	try {
		return devEnvironment = await Promise.any([
			fs.stat(`${getLaravelPath()}/.ddev`).then(() => 'ddev'),
			fs.stat(`${getLaravelPath()}/.lando.yml`).then(() => 'lando'),
		])
	} catch {
		return devEnvironment = 'native'
	}
}
