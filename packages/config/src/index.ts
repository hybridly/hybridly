import path from 'node:path'
import { loadConfig } from 'unconfig'

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
		root: 'resources',
		pages: 'pages',
		layouts: 'layouts',
		...config,
		domains: config?.domains === true
			? 'domains'
			: (config?.domains ?? false),
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

type WithRequired<T, K extends keyof T> = T & { [P in K]-?: T[P] }

export interface ResolvedHybridlyConfig extends WithRequired<HybridlyConfig, 'root' | 'domains' | 'pages' | 'layouts'> {
	pagesGlob: string
	domains: false | string
}

export interface HybridlyConfig {
	/** Whether to eagerly load page components. */
	eager?: boolean
	/** Directory where pages, layouts and domains subdirectories are. */
	root?: string
	/** Name of the domain directory. */
	domains?: boolean | string
	/** Name of the page directory. */
	pages?: string
	/** Name of the layout directory. */
	layouts?: string
}
