import { PluginOption } from 'vite'
import defineOptions from 'unplugin-vue-define-options/vite'
import layout from './layout'
import type { Options } from './types'

/** Registers `unplugin-vue-define-options`. */
function plugin(options: Options = {}): PluginOption[] {
	return [
		defineOptions(),
		options.layout !== false && layout(options.layout),
	]
}

export { layout, defineOptions, Options }
export default plugin
