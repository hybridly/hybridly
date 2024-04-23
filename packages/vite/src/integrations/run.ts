import run from 'vite-plugin-run'
import type { Runner } from 'vite-plugin-run'
import type { ViteOptions } from '../types'

function getRunOptions(options: ViteOptions): Runner[] {
	if (options.run === false) {
		return []
	}

	const php = process.env.PHP_EXECUTABLE_PATH ?? 'php'

	return [
		{
			name: 'Generate TypeScript types',
			run: [php, 'artisan', 'hybridly:types', (options.allowTypeGenerationFailures !== false) ? '--allow-failures' : ''].filter(Boolean),
			pattern: [
				'+(app|src)/**/*Data.php',
				'+(app|src)/**/Enums/*.php',
				'+(app|src)/**/Middleware/HandleHybridRequests.php',
			],
		},
		{
			name: 'Generate i18n',
			run: [php, 'artisan', 'hybridly:i18n'],
			pattern: 'lang/**/*.php',
		},
		...options.run ?? [],
	]
}

export { Runner, getRunOptions, run }
