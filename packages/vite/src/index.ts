import type { DynamicConfiguration } from '@hybridly/core'
import initialize from './config'
import { loadConfiguration } from './config/load'
import { getRunOptions, run } from './integrations/run'
import { hybridlyImports } from './integrations/unplugins'
import { getVueOptions, vue } from './integrations/vue'
import { killSwitch } from './kill-switch'
import laravel from './laravel'
import layout from './layout'
import { warnOnLocalBuilds } from './local-build'
import type { ViteOptions } from './types'

type Options = ViteOptions | ((config: DynamicConfiguration) => ViteOptions | Promise<ViteOptions>)

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

export { hybridlyImports, layout, ViteOptions as Options }
