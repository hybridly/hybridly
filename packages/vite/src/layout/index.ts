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
	const layoutsDirectory = options?.directory ?? path.resolve(process.cwd(), 'resources', 'views', 'layouts')
	const templateRegExp = options?.templateRegExp ?? TEMPLATE_LAYOUT_REGEX
	const resolveLayoutPath = (layoutName: string) => {
		const [domain, layout] = layoutName.includes(':')
			? layoutName.split(':')
			: [undefined, layoutName]

		const resolve = options?.resolve ?? ((layout: string, domain?: string) => {
			if (!domain) {
				return path.resolve(layoutsDirectory, `${layout}.vue`)
			}

			return path.resolve(process.cwd(), 'resources', 'domains', domain, 'layouts', `${layout}.vue`)
		})

		return normalizePath(resolve(layout, domain)).replaceAll('\\', '/')
	}

	debug.layout('Resolved options:', {
		defaultLayoutName,
		layoutsDirectory,
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
					import ${importName(i)} from '${resolveLayoutPath(layoutName)}';
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
