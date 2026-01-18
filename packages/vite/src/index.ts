import type { DynamicConfiguration } from '@hybridly/core'
import laravel from './laravel'
import initialize from './config'
import layout from './layout'
import type { ViteOptions } from './types'
import { getRunOptions, run } from './integrations/run'
import { getVueOptions, vue } from './integrations/vue'
import { loadConfiguration } from './config/load'
import { killSwitch } from './kill-switch'
import { warnOnLocalBuilds } from './local-build'
import { hybridlyImports } from './integrations/unplugins'

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
		resolvedOptions.vue !== false && vue(getVueOptions(resolvedOptions)),
		resolvedOptions.killSwitch !== false && killSwitch(),
		resolvedOptions.warnOnLocalBuilds !== false && warnOnLocalBuilds(),
	]
}

export { layout, ViteOptions as Options, hybridlyImports }
