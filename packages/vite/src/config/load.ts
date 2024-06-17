import type { DynamicConfiguration } from '@hybridly/core'
import { execSync } from '../utils'
import { determineDevEnvironment, getPhpExecutable } from './env'

export async function loadConfiguration(): Promise<DynamicConfiguration> {
	try {
		const php = (await getPhpExecutable()).join(' ')
		const { stdout } = await execSync(`${php} artisan hybridly:config`)
		return JSON.parse(stdout)
	} catch (e) {
		console.error('Could not load configuration from [php artisan hybridly:config].')

		if ((e as any).stdout) {
			console.error((e as any).stdout)
		}

		if (await determineDevEnvironment() === 'ddev') {
			console.error('This is possibly caused by not starting ddev first.')
		} else if (await determineDevEnvironment() === 'lando') {
			console.error('This is possibly caused by not starting lando first.')
		}

		throw e
	}
}
