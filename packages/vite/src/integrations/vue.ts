import { merge } from '@hybridly/utils'
import vue from '@vitejs/plugin-vue'
import type { ViteOptions } from '../types'

type VueOptions = Parameters<typeof vue>[0]

function getVueOptions(options: ViteOptions): VueOptions {
	if (options.vue === false) {
		return
	}

	return merge<VueOptions>({
		template: {
			transformAssetUrls: {
				base: null,
				includeAbsolute: false,
			},
		},
	}, options.vue ?? {})
}

export { VueOptions, getVueOptions, vue }
