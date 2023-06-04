import type { Plugin } from 'vite'
import { LAYOUT_PLUGIN_NAME } from '../constants'
import type { Configuration, ViteOptions } from '../types'
import { debug } from '../utils'

const TEMPLATE_LAYOUT_REGEX = /<template +layout(?: *= *['"]((?:[\w\/\-_,:](?:,\ )?)+)['"] *)?>/
const TYPESCRIPT_REGEX = /lang=['"]ts['"]/

export default (options: ViteOptions, config: Configuration): Plugin => {
	const defaultLayoutName = options?.layout?.defaultLayoutName?.replace('.vue', '') ?? 'default'
	const templateRegExp = options?.layout?.templateRegExp ?? TEMPLATE_LAYOUT_REGEX

	debug.layout('Resolved options:', {
		defaultLayoutName,
	})

	return {
		name: LAYOUT_PLUGIN_NAME,
		enforce: 'pre',
		transform: (code: string, id: string) => {
			if (!templateRegExp.test(code)) {
				return
			}

			return code.replace(templateRegExp, (_, layoutName) => {
				const isTypeScript = TYPESCRIPT_REGEX.test(code)
				const layouts: string[] = layoutName?.toString()?.replaceAll(' ', '').split(',') ?? [defaultLayoutName]
				const importName = (i: number) => `__hybridly_layout_${i}`
				const exports = layouts.map((_, i) => importName(i))
				const imports = layouts.reduce((imports, layoutName, i) => `
					${imports}
					import ${importName(i)} from '${resolveLayoutImportPath(layoutName, config)}';
				`, '').trim()

				debug.layout(`Resolved layouts "${layouts.join(', ')}":`, {
					sourceFile: id,
					layouts,
					imports,
				})

				return `
					<script${isTypeScript ? ' lang="ts"' : ''}>
					${imports}
					export default { layout: [${exports.join(', ')}] }
					</script>
					<template>
				`
			})
		},
	}
}

/**
 * Resolves a layout by its name.
 */
function resolveLayoutImportPath(name: string, config: Configuration) {
	const { path } = config.components.layouts.find((layout) => layout.identifier === name) ?? {}

	if (!path) {
		throw new Error(`Layout [${name}] could not be found.`)
	}

	return path
}
