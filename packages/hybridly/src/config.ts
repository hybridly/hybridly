import path from 'node:path'
import { loadConfig } from 'unconfig'
import type { RouterContextOptions, ResolveComponent, Plugin } from '@hybridly/core'
import type { ProgressOptions } from '@hybridly/progress'

export function resolvePageGlob(config: ResolvedHybridlyConfig) {
	if (config.domains !== false) {
		return `/${config.root}/${config.domains}/**/${config.pages}/**/*.vue`
	}

	return `/${config.root}/${config.pages}/**/*.vue`
}

export function resolveDirectory(name: string, config: ResolvedHybridlyConfig, domain?: string) {
	if (config.domains !== false && domain !== undefined) {
		return path.resolve(process.cwd(), config.root, config.domains, domain, name)
	}

	return path.resolve(process.cwd(), config.root, name)
}

export function resolvePagesDirectory(config: ResolvedHybridlyConfig, domain?: string) {
	return resolveDirectory(config.pages, config, domain)
}

export function resolveLayoutsDirectory(config: ResolvedHybridlyConfig, domain?: string) {
	return resolveDirectory(config.layouts, config, domain)
}

export async function loadHybridlyConfig(): Promise<ResolvedHybridlyConfig> {
	const { config } = await loadConfig<HybridlyConfig>({
		sources: {
			files: 'hybridly.config',
		},
	})

	const _default: Omit<ResolvedHybridlyConfig, 'pagesGlob'> = {
		id: 'root',
		eager: true,
		domains: false,
		root: 'resources',
		pages: 'pages',
		layouts: 'layouts',
		...config,
	}

	return {
		..._default,
		pagesGlob: resolvePageGlob(_default as ResolvedHybridlyConfig),
	}
}

/**
 * Defines the configuration for Hybridly.
 */
export function defineConfig(config: HybridlyConfig) {
	return config
}

export interface ResolvedHybridlyConfig extends HybridlyConfig {
	pagesGlob: string
	root: string
	domains: false | string
	pages: string
	layouts: string
	eager: boolean
}

export interface HybridlyConfig {
	/** ID of the app element. */
	id?: string
	/** Directory where pages, layouts and domains subdirectories are. */
	root?: string
	/** Name of the domain directory. */
	domains?: false | string
	/** Name of the page directory. */
	pages?: string
	/** Name of the layout directory. */
	layouts?: string
	/** Whether to eagerly load page components. */
	eager?: boolean
	/** Clean up the host element's payload dataset after loading. */
	cleanup?: boolean
	/** Whether to set up the devtools plugin. */
	devtools?: boolean
	/** A custom component resolution option. */
	resolve?: ResolveComponent
	/** Custom history state serialization functions. */
	serializer?: RouterContextOptions['serializer']
	/** Progressbar options. */
	progress?: false | Partial<ProgressOptions>
	/** List of Hybridly plugins. */
	plugins?: Plugin[]
}
