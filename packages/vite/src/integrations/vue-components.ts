import vueComponents from 'unplugin-vue-components/vite'
import { HeadlessUiResolver } from 'unplugin-vue-components/resolvers'
import iconsResolver from 'unplugin-icons/resolver'
import type { ComponentResolver } from 'unplugin-vue-components/types'
import { merge } from '@hybridly/utils'
import type { Configuration, ViteOptions } from '../types'
import { isPackageInstalled, toKebabCase } from '../utils'

type VueComponentsOptions = Parameters<typeof vueComponents>[0] & {
	/** Name of the Link component. */
	linkName?: string
	/** Custom prefix for Headless UI components. */
	headlessUiPrefix?: string
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

function getVueComponentsOptions(options: ViteOptions, config: Configuration): VueComponentsOptions {
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
			dirs: [
				`./${config.architecture.root}/components`,
			],
			directoryAsNamespace: true,
			dts: '.hybridly/components.d.ts',
			resolvers: overrideResolvers || [
				...(hasIcons ? [iconsResolver({ customCollections })] : []),
				...(hasHeadlessUI ? [HeadlessUiResolver({ prefix: options?.vueComponents?.headlessUiPrefix ?? 'Headless' })] : []),
				ProvidedComponentListResolver(config),
				HybridlyResolver(options.vueComponents?.linkName),
			],
		},
		options.vueComponents ?? {},
		{ overwriteArray: false },
	)
}

export function ProvidedComponentListResolver(config: Configuration): ComponentResolver {
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
