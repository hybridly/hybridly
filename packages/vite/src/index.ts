import laravel from './laravel'
import initialize from './config'
import layout from './layout'
import type { ViteOptions } from './types'
import { getRunOptions, run } from './integrations/run'
import { getAutoImportsOptions, autoimport, HybridlyImports } from './integrations/auto-imports'
import { getVueComponentsOptions, vueComponents, HybridlyResolver, HybridlyLinkResolver, ProvidedComponentListResolver } from './integrations/vue-components'
import { getIconsOptions, icons } from './integrations/icons'
import { getVueOptions, vue } from './integrations/vue'
import { loadConfiguration } from './config/load'
import { killSwitch } from './kill-switch'
import { warnOnLocalBuilds } from './local-build'

export default async function plugin(options: ViteOptions = {}) {
	const config = await loadConfiguration(options)

	return [
		initialize(options, config),
		layout(options, config),
		options.laravel !== false && laravel(options, config),
		options.run !== false && run(getRunOptions(options)),
		options.vueComponents !== false && vueComponents(await getVueComponentsOptions(options, config)),
		options.autoImports !== false && autoimport(getAutoImportsOptions(options, config)),
		options.icons !== false && icons(getIconsOptions(options, config)),
		options.vue !== false && vue(getVueOptions(options)),
		options.killSwitch !== false && killSwitch(),
		options.warnOnLocalBuilds !== false && warnOnLocalBuilds(),
	]
}

export { layout, ViteOptions as Options, HybridlyImports, HybridlyResolver, HybridlyLinkResolver, ProvidedComponentListResolver }
