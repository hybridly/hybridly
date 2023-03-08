import path from 'node:path'
import type { ResolvedHybridlyConfig } from '@hybridly/config'
import vueComponents from 'unplugin-vue-components/vite'
import { HeadlessUiResolver } from 'unplugin-vue-components/resolvers'
import iconsResolver from 'unplugin-icons/resolver'
import type { ComponentResolver } from 'unplugin-vue-components/types'
import { merge } from '@hybridly/utils'
import glob from 'fast-glob'
import type { ViteOptions } from '../types'
import { getSubstringBetween, isPackageInstalled, toKebabCase } from '../utils'

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
	const isUsingDomains = config.domains !== false

	return merge<VueComponentsOptions>(
		{
			dirs: [
				`./${config.root}/components`,
			],
			directoryAsNamespace: true,
			dts: '.hybridly/components.d.ts',
			resolvers: overrideResolvers || [
				...(hasIcons ? [iconsResolver({ customCollections })] : []),
				...(hasHeadlessUI ? [HeadlessUiResolver()] : []),
				...(isUsingDomains ? [DomainComponentsResolver(config)] : []),
				HybridlyResolver(options.vueComponents?.linkName),
			],
		},
		options.vueComponents ?? {},
		{ overwriteArray: false },
	)
}

export function resolveComponentUsingPaths(config: ResolvedHybridlyConfig, paths: string[], name: string, resolve: (...part: string[]) => string) {
	if (!config.domains) {
		return
	}

	const kebabName = `${toKebabCase(name)}.vue`

	for (const possiblePath of paths) {
		const domain = getSubstringBetween(possiblePath, `${resolve(config.root, config.domains)}/`, '/components/')

		if (!domain) {
			continue
		}

		const kebabPath = toKebabCase(possiblePath.replaceAll('/', '-')).replaceAll('--', '-').replace('components-', '')

		if (kebabPath.endsWith(kebabName) && kebabName.includes(domain)) {
			return possiblePath
		}
	}
}

function DomainComponentsResolver(config: ResolvedHybridlyConfig): ComponentResolver {
	return {
		type: 'component',
		resolve: (name: string) => {
			if (config.domains === false) {
				return
			}

			return resolveComponentUsingPaths(
				config,
				glob.sync(`${path.resolve(process.cwd(), config.root, config.domains as string)}/**/*.vue`),
				name,
				path.resolve,
			)
		},
	}
}

export { VueComponentsOptions, getVueComponentsOptions, vueComponents }
