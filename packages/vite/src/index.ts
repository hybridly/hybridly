import { PluginOption } from 'vite'
import laravel from 'vite-plugin-laravel'
import defineOptions from 'unplugin-vue-define-options/vite'
import layout from './layout'
import type { Options } from './types'

/** Registers `vite-plugin-laravel` and `unplugin-vue-define-options`. */
function plugin(options: Options = {}): PluginOption[] {
	return [
		laravel(options),
		layout(options),
		defineOptions(),
	]
}

export * from 'vite-plugin-laravel'
export { layout, defineOptions, Options }
export default plugin
