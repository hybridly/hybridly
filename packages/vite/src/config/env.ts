import fs from 'node:fs'
import { loadEnv } from 'vite'

export function getEnv() {
	return { ...process.env, ...loadEnv('mock', process.cwd(), '') }
}

let phpExecutable = null

export function getPhpExecutable(): string[] {
	if (phpExecutable) {
		return phpExecutable
	}

	const env = getEnv()

	const php = (env.PHP_EXECUTABLE_PATH ?? 'php').split(' ')

	if (!env.PHP_EXECUTABLE_PATH) {
		const devEnvironment = determineDevEnvironment()

		if (devEnvironment === 'ddev') {
			php.unshift('ddev')
		} else if (devEnvironment === 'lando') {
			php.unshift('lando')
		}
	}

	return phpExecutable = php
}

let devEnvironment = null

export function determineDevEnvironment() {
	if (devEnvironment !== null) {
		return devEnvironment
	}

	devEnvironment = ''

	if (fs.existsSync(`${process.cwd()}/.ddev`)) {
		devEnvironment = 'ddev'
	} else if (fs.existsSync(`${process.cwd()}/.lando.yml`)) {
		devEnvironment = 'lando'
	}

	return devEnvironment
}
