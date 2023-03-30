import path from 'node:path'
import fs from 'node:fs'
import type { HybridlyConfig } from 'hybridly/config'
import type { ViteOptions } from '../types'

export function generateTsConfig(options: ViteOptions, config: HybridlyConfig) {
	const tsconfig = {
		compilerOptions: {
			target: 'esnext',
			module: 'esnext',
			moduleResolution: 'node',
			strict: true,
			jsx: 'preserve',
			sourceMap: true,
			resolveJsonModule: true,
			esModuleInterop: true,
			allowSyntheticDefaultImports: true,
			lib: [
				'esnext',
				'dom',
			],
			types: [
				'vite/client',
				'hybridly/client',
				...(options.icons !== false ? ['unplugin-icons/types/vue'] : []),
			],
			baseUrl: '..',
			paths: {
				'#/*': [
					'.hybridly/*',
				],
				'~/*': [
					'./*',
				],
				'@/*': [
					`./${config.root}/*`,
				],
			},
		},
		include: [
			`../${config.root}/**/*`,
			'./*',
		],
		exclude: [
			'../public/**/*',
			'../node_modules',
			'../vendor',
		],
	}

	write(JSON.stringify(tsconfig, null, 2), 'tsconfig.json')
}

export function generateLaravelIdeaHelper(config: HybridlyConfig) {
	const ideJson = {
		$schema: 'https://laravel-ide.com/schema/laravel-ide-v2.json',
		completions: [
			...(config.domains ? [] : [{
				complete: 'directoryFiles',
				options: {
					directory: `/${config.root}/${config.pages}`,
					suffixToClear: '.vue',
				},
				condition: [
					{
						functionNames: ['hybridly'],
						parameters: [1],
					},
				],
			}]),
		],
	}

	write(JSON.stringify(ideJson, null, 2), 'ide.json')
}

function write(data: any, filename: string) {
	const hybridlyPath = path.resolve(process.cwd(), '.hybridly')

	if (!fs.existsSync(hybridlyPath)) {
		fs.mkdirSync(hybridlyPath)
	}

	fs.writeFileSync(path.resolve(hybridlyPath, filename), data, {
		encoding: 'utf-8',
	})
}
