import type { DynamicConfiguration } from '@hybridly/core'
import laravel from './laravel'
import initialize from './config'
import layout from './layout'
import type { ViteOptions } from './types'
import { getRunOptions, run } from './integrations/run'
import { HybridlyImports, autoimport, getAutoImportsOptions } from './integrations/auto-imports'
import { HybridlyLinkResolver, HybridlyResolver, ProvidedComponentListResolver, getVueComponentsOptions, vueComponents } from './integrations/vue-components'
import { getIconsOptions, icons } from './integrations/icons'
import { getVueOptions, vue } from './integrations/vue'
import { loadConfiguration } from './config/load'
import { killSwitch } from './kill-switch'
import { warnOnLocalBuilds } from './local-build'

type Options = ViteOptions | ((config: DynamicConfiguration) => (ViteOptions | Promise<ViteOptions>))

export default async function plugin(options: Options = {}) {
	const config = await loadConfiguration()
	const resolvedOptions = typeof options === 'function'
		? await options(config)
		: options

	return [
		initialize(resolvedOptions, config),
		layout(resolvedOptions, config),
		resolvedOptions.laravel !== false && laravel(resolvedOptions, config),
		resolvedOptions.run !== false && run(await getRunOptions(resolvedOptions)),
		resolvedOptions.vueComponents !== false && vueComponents(await getVueComponentsOptions(resolvedOptions, config)),
		resolvedOptions.autoImports !== false && autoimport(getAutoImportsOptions(resolvedOptions, config)),
		resolvedOptions.icons !== false && icons(getIconsOptions(resolvedOptions, config)),
		resolvedOptions.vue !== false && vue(getVueOptions(resolvedOptions)),
		resolvedOptions.killSwitch !== false && killSwitch(),
		resolvedOptions.warnOnLocalBuilds !== false && warnOnLocalBuilds(),
	]
}

export { layout, ViteOptions as Options, HybridlyImports, HybridlyResolver, HybridlyLinkResolver, ProvidedComponentListResolver }
