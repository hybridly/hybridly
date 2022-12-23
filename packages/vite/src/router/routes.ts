import { exec } from 'node:child_process'
import { promisify } from 'node:util'
import type { RouterOptions } from '../types'
import { write } from './typegen'
const shell = promisify(exec)

export async function fetchRoutingFromArtisan(options: RouterOptions) {
	try {
		const php = options.php ?? 'php'
		const result = await shell(`${php} artisan hybridly:routes`)
		const routing = JSON.parse(result.stdout)

		write(options, routing)

		return routing
	} catch {}
}
