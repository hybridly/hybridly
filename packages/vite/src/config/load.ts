import { promisify } from 'node:util'
import { exec } from 'node:child_process'
import type { DynamicConfiguration } from '@hybridly/core'
import type { ViteOptions } from '../types'
const shell = promisify(exec)

export async function loadConfiguration(options: ViteOptions): Promise<DynamicConfiguration> {
	try {
		const php = options.php ?? process.env.PHP_EXECUTABLE_PATH ?? 'php'
		const { stdout } = await shell(`${php} artisan hybridly:config`)
		return JSON.parse(stdout)
	} catch (e) {
		console.error('Could not load configuration from [php artisan].')
		throw e
	}
}
