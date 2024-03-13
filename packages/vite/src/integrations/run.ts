import path from 'node:path'
import run from 'vite-plugin-run'
import type { Runner } from 'vite-plugin-run'
import type { ResolvedOptions } from '../types'
import { getPhpExecutable } from '../config/env'

async function getRunOptions(options: ResolvedOptions): Runner[] {
	if (options.run === false) {
		return []
	}

	// Explicit typing is needed to please TypeScript
	const php: string[] = await getPhpExecutable(options.laravelPath)

	return [
		{
			name: 'Generate TypeScript types',
			run: [php, path.resolve(options.laravelPath, 'artisan'), 'hybridly:types'],
			pattern: [
				'+(app|src)/**/*Data.php',
				'+(app|src)/**/Enums/*.php',
				'+(app|src)/**/Middleware/HandleHybridRequests.php',
			],
		},
		{
			name: 'Generate i18n',
			run: [php, path.resolve(options.laravelPath, 'artisan'), 'hybridly:i18n'],
			pattern: 'lang/**/*.php',
		},
		...options.run ?? [],
	]
}

export { Runner, getRunOptions, run }
