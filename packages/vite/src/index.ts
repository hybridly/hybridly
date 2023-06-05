import laravel from 'laravel-vite-plugin'
import initialize from './config'
import layout from './layout'
import type { ViteOptions } from './types'
import { getRunOptions, run } from './integrations/run'
import { getLaravelOptions } from './integrations/laravel'
import { getAutoImportsOptions, autoimport, HybridlyImports } from './integrations/auto-imports'
import { getVueComponentsOptions, vueComponents, HybridlyResolver } from './integrations/vue-components'
import { getIconsOptions, icons } from './integrations/icons'
import { getVueOptions, vue } from './integrations/vue'
import { loadConfiguration } from './config/load'

export default async function plugin(options: ViteOptions = {}) {
	const config = await loadConfiguration(options)

	return [
		initialize(options, config),
		layout(options, config),
		options.laravel !== false && laravel(getLaravelOptions(options, config)),
		options.run !== false && run(getRunOptions(options)),
		options.vueComponents !== false && vueComponents(getVueComponentsOptions(options, config)),
		options.autoImports !== false && autoimport(getAutoImportsOptions(options, config)),
		options.icons !== false && icons(getIconsOptions(options, config)),
		options.vue !== false && vue(getVueOptions(options)),
	]
}

export { layout, ViteOptions as Options, HybridlyImports, HybridlyResolver }
