import path from 'node:path'
import fs from 'node:fs'
import type { Configuration, ViteOptions } from '../types'

export function generateTsConfig(options: ViteOptions, config: Configuration) {
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
					`./${config.architecture.root}/*`,
				],
			},
		},
		include: [
			...config.components.views.map(({ path }) => path),
			...config.components.layouts.map(({ path }) => path),
			`../${config.architecture.root}/**/*`,
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

export function generateLaravelIdeaHelper(config: Configuration) {
	const ideJson = {
		$schema: 'https://laravel-ide.com/schema/laravel-ide-v2.json',
		completions: [
			{
				complete: 'staticStrings',
				options: {
					strings: config.components.views.map(({ identifier }) => identifier),
				},
				condition: [
					{
						functionNames: ['hybridly'],
						parameters: [1],
					},
				],
			},
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
