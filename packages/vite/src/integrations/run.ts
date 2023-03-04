import run from 'vite-plugin-run'
import type { Runner } from 'vite-plugin-run'
import type { ViteOptions } from '../types'

function getRunOptions(options: ViteOptions): Runner[] {
	if (options.run === false) {
		return []
	}

	return [
		{
			name: 'Generate TypeScript types',
			run: ['php', 'artisan', 'hybridly:types'],
			pattern: [
				'+(app|src)/**/*Data.php',
				'+(app|src)/**/Enums/*.php',
				'+(app|src)/**/Middlewrare/HandleHybridRequests.php',
			],
		},
		{
			name: 'Generate i18n',
			run: ['php', 'artisan', 'hybridly:i18n'],
			pattern: 'lang/**/*.php',
		},
		...options.run ?? [],
	]
}

export { Runner, getRunOptions, run }
