/**
 * Resolver for `unplugin-vue-components`.
 * @see https://github.com/antfu/unplugin-vue-components
 */
export function MonolikitResolver(options: AutoImportResolverOptions = {}) {
	options = {
		linkName: 'RouterLink',
		...options,
	}

	return {
		type: 'component' as const,
		resolve: (name: string) => {
			if (name === options.linkName) {
				return {
					name: 'RouterLink',
					as: options.linkName,
					from: 'monolikit/vue',
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
