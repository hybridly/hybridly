import path from 'node:path'
import type { Plugin } from 'vite'
import { normalizePath } from 'vite'
import { LAYOUT_PLUGIN_NAME } from '../constants'
import type { LayoutOptions } from '../types'
import { debug } from '../utils'

const TEMPLATE_LAYOUT_REGEX = /<template +layout(?: *= *['"]((?:[\w\/\-_,:](?:,\ )?)+)['"] *)?>/
const TYPESCRIPT_REGEX = /lang=['"]ts['"]/

/**
 * A basic Vite plugin that adds a <template layout="name"> syntax to Vite SFCs.
 * It must be used before the Vue plugin.
 */
export default (options: LayoutOptions = {}): Plugin => {
	const defaultLayoutName = options?.defaultLayoutName?.replace('.vue', '') ?? 'default'
	const viewsDirectory = options?.views ?? path.resolve(process.cwd(), 'resources', 'views')
	const layoutsDirectoryName = options?.layoutsDirectoryName ?? 'layouts'
	const templateRegExp = options?.templateRegExp ?? TEMPLATE_LAYOUT_REGEX
	const getLayoutPath = options?.resolve ?? ((layoutName: string) => {
		if (!layoutName.includes(':')) {
			return normalizePath(path.resolve(viewsDirectory, layoutsDirectoryName, `${layoutName}.vue`)).replaceAll('\\', '/')
		}
		const [domain, layout] = layoutName.split(':')
		return normalizePath(path.resolve(viewsDirectory, domain, layoutsDirectoryName, `${layout}.vue`)).replaceAll('\\', '/')
	})

	debug.layout('Resolved options:', {
		defaultLayoutName,
		viewsDirectory,
		layoutsDirectoryName,
	})

	return {
		name: LAYOUT_PLUGIN_NAME,
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
					import ${importName(i)} from '${getLayoutPath(layoutName)}';
				`, '').trim()

				debug.layout(`Resolved layouts "${layouts.join(', ')}":`, {
					sourceFile: id,
					layouts,
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
