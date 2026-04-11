import run from 'vite-plugin-run'
import type { Runner } from 'vite-plugin-run'
import { getPhpExecutable } from '../config/env'
import type { ViteOptions } from '../types'

async function getRunOptions(options: ViteOptions): Promise<Runner[]> {
	if (options.run === false) {
		return []
	}

	// Explicit typing is needed to please TypeScript
	const php: string[] = await getPhpExecutable()

	return [
		{
			name: 'Generate TypeScript definitions',
			run: [...php, 'artisan', 'hybridly:types', (options.allowTypeGenerationFailures !== false) ? '--allow-failures' : ''].filter(Boolean),
			pattern: [
				'+(app|config|routes|src)/**/*.php',
			],
		},
		{
			name: 'Generate i18n',
			run: [...php, 'artisan', 'hybridly:i18n'],
			pattern: 'lang/**/*.php',
		},
		...options.run ?? [],
	]
}

export { getRunOptions, run, Runner }
