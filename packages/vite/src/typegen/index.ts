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

export function generateVueShims(options: ViteOptions) {
	if (options.shims === false) {
		return
	}

	const shims = `declare module '*.vue' {
	import type { DefineComponent } from 'vue'
	const component: DefineComponent<{}, {}, any>
	export default component
}`

	write(shims, 'vue-shims.d.ts')
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
