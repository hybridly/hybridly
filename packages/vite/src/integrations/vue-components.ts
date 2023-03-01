import type { ResolvedHybridlyConfig } from '@hybridly/config'
import vueComponents from 'unplugin-vue-components/vite'
import iconsResolver from 'unplugin-icons/resolver'
import type { ComponentResolver } from 'unplugin-vue-components/types'
import { merge } from '@hybridly/utils'
import type { ViteOptions } from '../types'

type VueComponentsOptions = Parameters<typeof vueComponents>[0] & {
	/** Name of the Link component. */
	linkName?: string
}

export type CustomResolvers = ComponentResolver | ComponentResolver[]
export type CustomComponentsOptions = Omit<VueComponentsOptions, 'dirs'>

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
	const customResolvers = options.customResolvers
		?	Array.isArray(options.customResolvers)
			? options.customResolvers
			: [options.customResolvers]
		: []

	return merge<VueComponentsOptions>(
		{
			globs: [
				`${config.root}/components/**/*.vue`,
				...(config.domains ? [`${config.root}/${config.domains}/**/components/**/*.vue`] : []),
			],
			dts: '.hybridly/components.d.ts',
			resolvers: [
				...(hasIcons ? [iconsResolver({ customCollections })] : []),
				HybridlyResolver(options.vueComponents?.linkName),
				...customResolvers,
			],
		},
		options.vueComponents ?? {},
		{ overwriteArray: false },
	)
}

export { VueComponentsOptions, getVueComponentsOptions, vueComponents }
