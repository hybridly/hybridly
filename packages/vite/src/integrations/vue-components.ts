import type { ResolvedHybridlyConfig } from 'hybridly'
import vueComponents from 'unplugin-vue-components/vite'
import iconsResolver from 'unplugin-icons/resolver'
import type { ViteOptions } from '../types'

type VueComponentsOptions = Parameters<typeof vueComponents>[0] & {
	/** Name of the Link component. */
	linkName?: string
}

function getVueComponentsOptions(options: ViteOptions, config: ResolvedHybridlyConfig): VueComponentsOptions {
	if (options.vueComponents === false) {
		return {}
	}

	const linkName = options.vueComponents?.linkName ?? 'RouterLink'
	const hasIcons = options?.icons !== false

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
			...options.vueComponents?.resolvers ?? [],
		],
	}
}

export { VueComponentsOptions, getVueComponentsOptions, vueComponents }
