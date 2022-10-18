import { exec } from 'node:child_process'
import { promisify } from 'node:util'
import type { RouterOptions } from '../types'
import { write } from './typegen'
const shell = promisify(exec)

export async function fetchRoutesFromArtisan(options: RouterOptions) {
	try {
		const php = options.php ?? 'php'
		const result = await shell(`${php} artisan hybridly:routes`)
		const routes = JSON.parse(result.stdout)

		write(options, routes)

		return routes
	} catch {}
}
