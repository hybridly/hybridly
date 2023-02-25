import type { ResolvedHybridlyConfig } from 'hybridly'
import vueComponents from 'unplugin-vue-components/vite'
import iconsResolver from 'unplugin-icons/resolver'
import type { ComponentResolver } from 'unplugin-vue-components/types'
import type { ViteOptions } from '../types'

type VueComponentsOptions = Parameters<typeof vueComponents>[0] & {
	/** Name of the Link component. */
	linkName?: string
}

export interface CustomComponentsOptions extends VueComponentsOptions {
	/** Custom component resolvers. */
	customResolvers?: ComponentResolver | ComponentResolver[]
}

function getVueComponentsOptions(options: ViteOptions, config: ResolvedHybridlyConfig): VueComponentsOptions {
	if (options.vueComponents === false) {
		return {}
	}

	const linkName = options.vueComponents?.linkName ?? 'RouterLink'
	const hasIcons = options?.icons !== false
	const customResolvers = options.vueComponents?.customResolvers
		?	Array.isArray(options.vueComponents?.customResolvers)
			? options.vueComponents?.customResolvers
			: [options.vueComponents?.customResolvers]
		: []

	return {
		globs: [
			`${config.root}/components/**/*.vue`,
			...(config.domains ? [`${config.root}/${config.domains}/**/components/**/*.vue`] : []),
		],
		dts: '.hybridly/components.d.ts',
		resolvers: [
			...(hasIcons ? [
				iconsResolver({
					customCollections: options?.customIcons?.collections,
				}),
			] : []),
			{
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
			},
			...customResolvers,
		],
	}
}

export { VueComponentsOptions, getVueComponentsOptions, vueComponents }
