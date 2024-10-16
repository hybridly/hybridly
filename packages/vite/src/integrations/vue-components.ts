import vueComponents from 'unplugin-vue-components/vite'
import { HeadlessUiResolver } from 'unplugin-vue-components/resolvers'
import iconsResolver from 'unplugin-icons/resolver'
import type { ComponentResolver } from 'unplugin-vue-components/types'
import { match, merge, wrap } from '@hybridly/utils'
import type { DynamicConfiguration } from '@hybridly/core'
import type { ViteOptions } from '../types'
import { importPackage, isPackageInstalled, toKebabCase } from '../utils'

type VueComponentsOptions = Parameters<typeof vueComponents>[0] & {
	/** Name of the Link component. */
	linkName?: string
	/** Name of the Deferred component. */
	deferredName?: string
	/** Name of the WhenVisible component. */
	whenVisibleName?: string
	/** Specify the prefix for the Headless UI integration, or disable it. */
	headlessUiPrefix?: string | false
	/** Specify the prefix for the Radix integration, or disable it. */
	radixPrefix?: string | false
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
		? wrap(options.overrideResolvers)
		: false

	const shouldImportHeadlessUi = isPackageInstalled('@headlessui/vue') && options.vueComponents?.headlessUiPrefix !== false
	const shouldImportRadix = isPackageInstalled('radix-vue') && options.vueComponents?.radixPrefix !== false

	return merge<VueComponentsOptions>(
		{
			dirs: [
				`./${config.architecture.root_directory}/${config.architecture.components_directory}`,
			],
			directoryAsNamespace: true,
			dts: '.hybridly/components.d.ts',
			resolvers: overrideResolvers || [
				...(hasIcons ? [iconsResolver({ customCollections })] : []),
				...(shouldImportHeadlessUi ? [HeadlessUiResolver({ prefix: options?.vueComponents?.headlessUiPrefix || 'Headless' })] : []),
				...(shouldImportRadix ? [await RadixResolver(options?.vueComponents?.radixPrefix || 'Radix')] : []),
				ProvidedComponentListResolver(config),
				HybridlyResolver(options, config),
			].filter(Boolean),
		},
		options.vueComponents ?? {},
		{ overwriteArray: false },
	)
}

export async function RadixResolver(prefix: string) {
	try {
		const radix = await importPackage('radix-vue/resolver')
		return radix.default?.({ prefix }) ?? radix?.({ prefix })
	} catch {}
}

export function HybridlyResolver(options: ViteOptions, config: DynamicConfiguration): ComponentResolver[] {
	return [
		HybridlyComponentsResolver(
			options.vueComponents
				? {
						deferredName: options?.vueComponents?.deferredName,
						linkName: options?.vueComponents?.linkName,
						whenVisibleName: options?.vueComponents?.whenVisibleName,
					}
				: undefined,
		),
		ProvidedComponentListResolver(config),
	]
}

interface HybridlyComponentsResolverOptions {
	deferredName?: string
	linkName?: string
	whenVisibleName?: string
}

export function HybridlyComponentsResolver(options?: HybridlyComponentsResolverOptions): ComponentResolver {
	const deferredName = options?.deferredName ?? 'Deferred'
	const whenVisibleName = options?.whenVisibleName ?? 'WhenVisible'
	const linkName = options?.linkName ?? 'RouterLink'

	return {
		type: 'component',
		resolve: (name: string) => match(name, {
			[deferredName]: {
				from: 'hybridly/vue',
				name: 'Deferred',
				as: deferredName,
			},
			[whenVisibleName]: {
				from: 'hybridly/vue',
				name: 'WhenVisible',
				as: whenVisibleName,
			},
			[linkName]: {
				from: 'hybridly/vue',
				name: 'RouterLink',
				as: linkName,
			},
			default: undefined,
		}),
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
