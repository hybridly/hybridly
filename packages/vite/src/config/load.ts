import { exec } from 'node:child_process'
import { promisify } from 'node:util'
import type { DynamicConfiguration } from '@hybridly/core'
import { determineDevEnvironment, getPhpExecutable } from './env'

const execSync = promisify(exec)

export async function loadConfiguration(): Promise<DynamicConfiguration> {
	try {
		const php = getPhpExecutable().join(' ')
		const { stdout } = await execSync(`${php} artisan hybridly:config`)
		return JSON.parse(stdout)
	} catch (e) {
		console.error('Could not load configuration from [php artisan hybridly:config].')

		if (determineDevEnvironment() === 'ddev') {
			console.error('This is possibly caused by not starting ddev first.')
		} else if (determineDevEnvironment() === 'lando') {
			console.error('This is possibly caused by not starting lando first.')
		}

		throw e
	}
}
