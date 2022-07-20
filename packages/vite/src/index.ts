import { PluginOption } from 'vite'
import layout from './layout'
import router from './router'
import type { Options } from './types'

/** Registers `unplugin-vue-define-options`. */
function plugin(options: Options = {}): PluginOption[] {
	return [
		options.layout !== false && layout(options.layout),
		options.router !== false && router(options.router),
	]
}

export { layout, router, Options }
export default plugin
