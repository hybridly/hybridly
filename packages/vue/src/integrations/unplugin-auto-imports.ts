/**
 * Resolver for `unplugin-vue-components`.
 * @see https://github.com/antfu/unplugin-vue-components
 */
export function HybridlyResolver(options: AutoImportResolverOptions = {}) {
	options = {
		linkName: 'Link',
		...options,
	}

	return {
		type: 'component' as const,
		resolve: (name: string) => {
			if (name === options.linkName) {
				return {
					name: 'Link',
					as: options.linkName,
					from: 'hybridly/vue',
				}
			}
		},
	}
}

export interface AutoImportResolverOptions {
	/**
	 * Custom name for the link component.
	 */
	linkName?: string
}
