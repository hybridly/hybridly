import path from 'node:path'
import run from 'vite-plugin-run'
import type { Runner } from 'vite-plugin-run'
import type { ResolvedOptions } from '../types'

function getRunOptions(options: ResolvedOptions): Runner[] {
	if (options.run === false) {
		return []
	}

	const php = process.env.PHP_EXECUTABLE_PATH ?? 'php'

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
