import type { ResolvedHybridlyConfig } from '@hybridly/config'
import { merge } from '@hybridly/utils'
import autoimport from 'unplugin-auto-import/vite'
import type { ViteOptions } from '../types'

type AutoImportOptions = Parameters<typeof autoimport>[0]

export const HybridlyImports = {
	'hybridly/vue': [
		'useProperty',
		'useTypedProperty',
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

function getAutoImportsOptions(options: ViteOptions, config: ResolvedHybridlyConfig): AutoImportOptions {
	if (options.autoImports === false) {
		return
	}

	return merge<AutoImportOptions>(
		{
			vueTemplate: true,
			dts: '.hybridly/auto-imports.d.ts',
			dirs: [
				`${config.root}/utils`,
				`${config.root}/composables`,
			],
			imports: [
				'vue',
				'vue/macros',
				'@vueuse/core',
				'@vueuse/head',
				HybridlyImports,
			],
		},
		options.autoImports ?? {},
		{ overwriteArray: false },
	)
}

export { getAutoImportsOptions, AutoImportOptions, autoimport }
