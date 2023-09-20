import { merge } from '@hybridly/utils'
import autoimport from 'unplugin-auto-import/vite'
import type { DynamicConfiguration } from '@hybridly/core'
import type { ViteOptions } from '../types'
import { isPackageInstalled } from '../utils'

type AutoImportOptions = Parameters<typeof autoimport>[0]

export const HybridlyImports = {
	'hybridly/vue': [
		'useProperty',
		'setProperty',
		'useRefinements',
		'useTable',
		'useBulkSelect',
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
		'useRoute',
	],
	'hybridly': [
		'router',
		'route',
		'can',
	],
}

function getAutoImportsOptions(options: ViteOptions, config: DynamicConfiguration): AutoImportOptions {
	if (options.autoImports === false) {
		return
	}

	const presets = ['@vueuse/core', '@vueuse/head', 'vue-i18n'] as const
	const custom = {
		'@unhead/vue': [
			'useHead',
			'useSeoMeta',
		],
		'@innocenzi/utils': [
			'match',
			'invoke',
			'batchInvoke',
			'asyncInvoke',
		],
	}

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
				...Object.entries(custom).filter(([pkg]) => isPackageInstalled(pkg)).map(([pkg, imports]) => ({ [pkg]: imports })),
				HybridlyImports,
			],
		},
		options.autoImports ?? {},
		{ overwriteArray: false },
	)
}

export { getAutoImportsOptions, AutoImportOptions, autoimport }
