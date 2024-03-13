import type { DynamicConfiguration } from '@hybridly/core'
import { execSync } from '../utils'
import { determineDevEnvironment, getPhpExecutable } from './env'

export async function loadConfiguration(laravelPath: string, basePath: string): Promise<DynamicConfiguration> {
	try {
		const php = (await getPhpExecutable(laravelPath)).join(' ')
		const { stdout } = await execSync(`${php} artisan hybridly:config`)

		if (stderr) {
			throw stderr
		}
		const config = JSON.parse(stdout)

		if (basePath !== laravelPath && basePath.startsWith(laravelPath)) {
			const base = basePath.slice(laravelPath.length + 1)

			const toBase = (path: string) => {
				return path.startsWith(base) ? path.slice(base.length + 1) : path
			}
			['views', 'components', 'layouts'].forEach((key) => {
				if (Array.isArray(config.components[key])) {
					config.components[key].forEach((cmp) => {
						if (cmp.path) {
							cmp.path = toBase(cmp.path)
						}
					})
				}
			})
		}

		return config
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
