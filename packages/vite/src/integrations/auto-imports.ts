import { merge } from '@hybridly/utils'
import autoimport from 'unplugin-auto-import/vite'
import type { Configuration, ViteOptions } from '../types'
import { isPackageInstalled } from '../utils'

type AutoImportOptions = Parameters<typeof autoimport>[0]

export const HybridlyImports = {
	'hybridly/vue': [
		'useProperty',
		'useRefinements',
		'useProperties',
		'useBackForward',
		'useContext',
		'useForm',
		'useDialog',
		'useHistoryState',
		'usePaginator',
		'defineLayout',
		'defineLayoutProperties',
		'registerHook',
	],
	'hybridly': [
		'router',
		'route',
		'current',
		'can',
	],
}

function getAutoImportsOptions(options: ViteOptions, config: Configuration): AutoImportOptions {
	if (options.autoImports === false) {
		return
	}

	const presets = ['@vueuse/core', '@vueuse/head', 'vue-i18n'] as const

	return merge<AutoImportOptions>(
		{
			vueTemplate: true,
			dts: '.hybridly/auto-imports.d.ts',
			dirs: [
				`${config.architecture.root}/utils`,
				`${config.architecture.root}/composables`,
				...config.components.directories.map((directory) => `${directory}/**/*.ts`),
			],
			imports: [
				'vue',
				'vue/macros',
				...presets.filter((pkg) => isPackageInstalled(pkg)),
				HybridlyImports,
			],
		},
		options.autoImports ?? {},
		{ overwriteArray: false },
	)
}

export { getAutoImportsOptions, AutoImportOptions, autoimport }
