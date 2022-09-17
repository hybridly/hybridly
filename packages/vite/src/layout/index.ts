import path from 'node:path'
import type { Plugin } from 'vite'
import { normalizePath } from 'vite'
import { LAYOUT_PLUGIN_NAME } from '../constants'
import type { LayoutOptions } from '../types'
import { debug } from '../utils'

const TEMPLATE_LAYOUT_REGEX = /<template +layout(?: *= *['"](?:(?:(\w+):)?(\w+))['"] *)?>/
const TYPESCRIPT_REGEX = /lang=['"]ts['"]/

/**
 * A basic Vite plugin that adds a <template layout="name"> syntax to Vite SFCs.
 * It must be used before the Vue plugin.
 */
export default (options: LayoutOptions = {}): Plugin => {
	const base = options?.directory
		? options?.directory
		: path.resolve(process.cwd(), 'resources', 'views', 'layouts')

	const layoutPath = options?.resolve
		? options.resolve
		: (layoutName: string) => normalizePath(path.resolve(base, `${layoutName ?? 'default'}.vue`)).replaceAll('\\', '/')

	debug.layout('Registered layout path:', base)

	return {
		name: LAYOUT_PLUGIN_NAME,
		transform: (code: string, id: string) => {
			if (!TEMPLATE_LAYOUT_REGEX.test(code)) {
				return
			}

			return code.replace(TEMPLATE_LAYOUT_REGEX, (_, __, layoutName) => {
				const isTypeScript = TYPESCRIPT_REGEX.test(code)
				const importPath = layoutPath(layoutName)

				debug.layout(`Resolved layout "${layoutName}":`, {
					sourceFile: id,
					importPath,
				})

				return `
					<script${isTypeScript ? ' lang="ts"' : ''}>
					import layout from '${importPath}'
					export default { layout }
					</script>
					<template>
				`
			})
		},
	}
}
