import path from 'node:path'
import { merge } from '@hybridly/utils'
import vue from '@vitejs/plugin-vue'
import type { ResolvedOptions } from '../types'

type VueOptions = Parameters<typeof vue>[0]

function getVueOptions(options: ResolvedOptions): VueOptions {
	if (options.vue === false) {
		return
	}

	return merge<VueOptions>(
		{
			template: {
				transformAssetUrls: {
					base: null,
					includeAbsolute: false,
				},
				...options.vue?.template,
			},
			script: {
				globalTypeFiles: [
					path.resolve(options.laravelPath, '.hybridly/php-types.d.ts'),
				],
				defineModel: true,
				...options.vue?.script,
			},
		},
		options.vue ?? {},
		{ overwriteArray: false },
	)
}

export { VueOptions, getVueOptions, vue }
