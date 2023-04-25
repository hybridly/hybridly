import path from 'node:path'
import { merge } from '@hybridly/utils'
import vue from '@vitejs/plugin-vue'
import type { ViteOptions } from '../types'

type VueOptions = Parameters<typeof vue>[0]

function getVueOptions(options: ViteOptions): VueOptions {
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
			},
			script: {
				globalTypeFiles: [
					path.resolve('.hybridly/back-end.d.ts'),
				],
			},
		},
		options.vue ?? {},
		{ overwriteArray: false },
	)
}

export { VueOptions, getVueOptions, vue }
