import type { ResolvedHybridlyConfig } from '@hybridly/config'
import autoimport from 'unplugin-auto-import/vite'
import type { ViteOptions } from '../types'

type AutoImportOptions = Parameters<typeof autoimport>[0]

function getAutoImportsOptions(options: ViteOptions, config: ResolvedHybridlyConfig): AutoImportOptions {
	if (options.autoImports === false) {
		return
	}

	return {
		vueTemplate: true,
		dts: '.hybridly/auto-imports.d.ts',
		dirs: [
			`${config.root}/utils`,
			`${config.root}/composables`,
			...options.autoImports?.dirs ?? [],
		],
		imports: [
			'vue',
			'vue/macros',
			'@vueuse/core',
			'@vueuse/head',
			{
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
			},
		],
		...options.autoImports,
	}
}

export { getAutoImportsOptions, AutoImportOptions, autoimport }
