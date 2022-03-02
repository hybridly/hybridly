/**
 * Resolver for `unplugin-vue-components`.
 * @see https://github.com/antfu/unplugin-vue-components
 */
export function SleightfulResolver(options: AutoImportResolverOptions = {}) {
	options = {
		linkName: 'Link',
		...options,
	}

	return {
		type: 'component',
		resolve: (name: string) => {
			if (name === options.linkName) {
				return { importName: name, path: '@sleightful/vue' }
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
