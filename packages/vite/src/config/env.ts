import fs from 'node:fs'
import { loadEnv } from 'vite'

let phpExecutable: string[]
let devEnvironment: 'ddev' | 'lando' | 'native'

/**
 * Gets all environment variables, including `.env` ones.
 */
export function getEnv() {
	return { ...process.env, ...loadEnv('mock', process.cwd(), '') }
}

export async function getPhpExecutable(): Promise<string[]> {
	if (phpExecutable) {
		return phpExecutable
	}

	const env = getEnv()
	const php = (env.PHP_EXECUTABLE_PATH ?? 'php').split(' ')

	if (!env.PHP_EXECUTABLE_PATH) {
		const devEnvironment = await determineDevEnvironment()

		if (devEnvironment === 'ddev') {
			php.unshift('ddev')
		} else if (devEnvironment === 'lando') {
			php.unshift('lando')
		}
	}

	return phpExecutable = php
}

export async function determineDevEnvironment() {
	if (devEnvironment) {
		return devEnvironment
	}

	if (fs.existsSync(`${process.cwd()}/.ddev`)) {
		devEnvironment = 'ddev'
	} else if (fs.existsSync(`${process.cwd()}/.lando.yml`)) {
		devEnvironment = 'lando'
	} else {
		devEnvironment = 'native'
	}

	return devEnvironment
}
