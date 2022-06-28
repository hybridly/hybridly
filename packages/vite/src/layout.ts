import path from 'node:path'
import makeDebugger from 'debug'
import { Plugin, normalizePath } from 'vite'
import { LayoutOptions } from './types'

const PLUGIN_NAME = 'vite:monolikit:layout'
const TEMPLATE_LAYOUT_REGEX = /<template +layout(?: *= *['"](?:(?:(\w+):)?(\w+))['"] *)?>/
const debug = makeDebugger(PLUGIN_NAME)

/**
 * A basic Vite plugin that adds a <template layout="name"> syntax to Vite SFCs.
 * It must be used before the Vue plugin.
 */
export default (options: LayoutOptions = {}): Plugin => {
	const base = options?.directory
		? options?.directory
		: path.resolve(process.cwd(), 'resources', 'views', 'layouts')
	const layoutPath = (layoutName: string) => normalizePath(path.resolve(base, `${layoutName ?? 'default'}.vue`)).replaceAll('\\', '/')

	debug('Registered layout path:', base)

	return {
		name: PLUGIN_NAME,
		transform: (code: string, id: string) => {
			if (!TEMPLATE_LAYOUT_REGEX.test(code)) {
				return
			}

			debug(`Found layout call in ${id}`)

			const isTypeScript = /lang=['"]ts['"]/.test(code)

			return code.replace(TEMPLATE_LAYOUT_REGEX, (_, __, layoutName) => {
				const importPath = layoutPath(layoutName)
				debug(`Resolved "${layoutName}": ${importPath}`)

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
