import path from 'node:path'
import { merge } from '@hybridly/utils'
import vue, { type Options } from '@vitejs/plugin-vue'
import type { ViteOptions } from '../types'

function getVueOptions(options: ViteOptions): Options {
	if (options.vue === false) {
		return {}
	}

	return merge<Options>(
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
					path.resolve('.hybridly/php-types.d.ts'),
				],
				defineModel: true,
				...options.vue?.script,
			},
		},
		options.vue ?? {},
		{ overwriteArray: false },
	)
}

export { Options as VueOptions, getVueOptions, vue }
