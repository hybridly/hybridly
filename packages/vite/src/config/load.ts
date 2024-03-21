import { exec } from 'node:child_process'
import { promisify } from 'node:util'
import type { DynamicConfiguration } from '@hybridly/core'

const execSync = promisify(exec)

export async function loadConfiguration(): Promise<DynamicConfiguration> {
	try {
		const php = process.env.PHP_EXECUTABLE_PATH ?? 'php'
		const { stdout } = await execSync(`${php} artisan hybridly:config`)
		return JSON.parse(stdout)
	} catch (e) {
		console.error('Could not load configuration from [php artisan].')
		throw e
	}
}
