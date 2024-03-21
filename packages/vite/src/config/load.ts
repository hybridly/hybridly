import { exec } from 'node:child_process'
import { promisify } from 'node:util'
import type { DynamicConfiguration } from '@hybridly/core'
import { loadEnv } from 'vite'

const execSync = promisify(exec)

export async function loadConfiguration(): Promise<DynamicConfiguration> {
	try {
		const env = { ...process.env, ...loadEnv('mock', process.cwd(), '') }
		const php = env.PHP_EXECUTABLE_PATH ?? 'php'
		const { stdout } = await execSync(`${php} artisan hybridly:config`)
		return JSON.parse(stdout)
	} catch (e) {
		console.error('Could not load configuration from [php artisan].')
		throw e
	}
}
