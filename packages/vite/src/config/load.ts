import { spawnSync } from 'node:child_process'
import path from 'node:path'
import type { DynamicConfiguration } from '@hybridly/core'

export async function loadConfiguration(laravelPath, basePath): Promise<DynamicConfiguration> {
	try {
		const php = process.env.PHP_EXECUTABLE_PATH ?? 'php'
		const child = spawnSync(php, [path.resolve(laravelPath, 'artisan'), 'hybridly:config', basePath], { encoding: 'utf8' })
		return JSON.parse(child.stdout)
	} catch (e) {
		console.error('Could not load configuration from [php artisan].')
		throw e
	}
}
