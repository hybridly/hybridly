import vueComponents from 'unplugin-vue-components/vite'
import iconsResolver from 'unplugin-icons/resolver'
import type { ComponentResolver } from 'unplugin-vue-components/types'
import { merge } from '@hybridly/utils'
import type { DynamicConfiguration } from '@hybridly/core'
import type { ViteOptions } from '../types'
import { toKebabCase } from '../utils'

type VueComponentsOptions = Parameters<typeof vueComponents>[0] & {
	/** Name of the Link component. */
	linkName?: string
}

export type CustomResolvers = ComponentResolver | ComponentResolver[]
export type CustomComponentsOptions = VueComponentsOptions

async function getVueComponentsOptions(options: ViteOptions, config: DynamicConfiguration): Promise<VueComponentsOptions> {
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

	return merge<VueComponentsOptions>(
		{
			dirs: [
				`./${config.architecture.root_directory}/${config.architecture.components_directory}`,
			],
			directoryAsNamespace: true,
			dts: '.hybridly/components.d.ts',
			resolvers: overrideResolvers || [
				...(hasIcons ? [iconsResolver({ customCollections })] : []),
				ProvidedComponentListResolver(config),
				HybridlyResolver(options.vueComponents?.linkName),
			],
		},
		options.vueComponents ?? {},
		{ overwriteArray: false },
	)
}

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

export function ProvidedComponentListResolver(config: DynamicConfiguration): ComponentResolver {
	function resolveComponentPath(name: string): string | undefined {
		const kebabName = toKebabCase(name)

		const path = config.components.components.find((view) => {
			const identifierAsComponentName = view.identifier
				.replace('::', '-')
				.replace('.', '-')

			return identifierAsComponentName === kebabName
		})?.path

		if (!path) {
			return
		}

		return `~/${path}`
	}

	return {
		type: 'component',
		resolve: (name: string) => resolveComponentPath(name),
	}
}

export { VueComponentsOptions, getVueComponentsOptions, vueComponents }
