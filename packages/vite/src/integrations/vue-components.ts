import type { ResolvedHybridlyConfig } from '@hybridly/config'
import vueComponents from 'unplugin-vue-components/vite'
import { HeadlessUiResolver } from 'unplugin-vue-components/resolvers'
import iconsResolver from 'unplugin-icons/resolver'
import type { ComponentResolver } from 'unplugin-vue-components/types'
import { merge } from '@hybridly/utils'
import type { ViteOptions } from '../types'
import { isPackageInstalled } from '../utils'

type VueComponentsOptions = Parameters<typeof vueComponents>[0] & {
	/** Name of the Link component. */
	linkName?: string
}

export type CustomResolvers = ComponentResolver | ComponentResolver[]
export type CustomComponentsOptions = VueComponentsOptions

export function HybridlyResolver(linkName: string = 'RouterLink') {
	return {
		type: 'component' as const,
		resolve: (name: string) => {
			if (name === linkName) {
				return {
					from: 'hybridly/vue',
					name: 'RouterLink',
					as: linkName,
				}
			}
		},
	}
}

function getVueComponentsOptions(options: ViteOptions, config: ResolvedHybridlyConfig): VueComponentsOptions {
	if (options.vueComponents === false) {
		return {}
	}

	const hasIcons = options?.icons !== false
	const customCollections = Array.isArray(options.customIcons)
		? options.customIcons
		: options.customIcons?.collections ?? []
	const overrideResolvers = options.overrideResolvers
		?	Array.isArray(options.overrideResolvers)
			? options.overrideResolvers
			: [options.overrideResolvers]
		: false

	const hasHeadlessUI = isPackageInstalled('@headlessui/vue')

	return merge<VueComponentsOptions>(
		{
			globs: config.domains !== false ? [
				`${config.root}/components/**/*.vue`,
				`${config.root}/${config.domains}/**/components/**/*.vue`,
			] : undefined,
			dirs: config.domains === false ? [
				`./${config.root}/components`,
			] : [],
			directoryAsNamespace: true,
			dts: '.hybridly/components.d.ts',
			resolvers: overrideResolvers || [
				...(hasIcons ? [iconsResolver({ customCollections })] : []),
				...(hasHeadlessUI ? [HeadlessUiResolver()] : []),
				HybridlyResolver(options.vueComponents?.linkName),
			],
		},
		options.vueComponents ?? {},
		{ overwriteArray: false },
	)
}

export { VueComponentsOptions, getVueComponentsOptions, vueComponents }
