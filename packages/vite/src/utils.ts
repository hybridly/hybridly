import makeDebugger from 'debug'
import { importModule, isPackageExists, resolveModule } from 'local-pkg'
import { LAYOUT_PLUGIN_NAME, CONFIG_PLUGIN_NAME } from './constants'

export const debug = {
	config: makeDebugger(CONFIG_PLUGIN_NAME),
	layout: makeDebugger(LAYOUT_PLUGIN_NAME),
}

export function isPackageInstalled(name: string, paths: string[] = [process.cwd()]) {
	return isPackageExists(name, { paths })
}

export function importPackage(name: string, paths: string[] = [process.cwd()]) {
	const mod = resolveModule(name, { paths })
	if (!mod) {
		console.warn(`Could not resolve package [${name}]`)
		return
	}

	return importModule(mod)
}

export function toKebabCase(key: string) {
	const result = key.replace(/([A-Z])/g, ' $1').trim()
	return result.split(' ').join('-').toLowerCase()
}

export function toPascalCase(str: string) {
	return capitalize(toCamelCase(str))
}

export function toCamelCase(str: string) {
	return str.replace(/-(\w)/g, (_, c) => (c ? c.toUpperCase() : ''))
}

export function capitalize(str: string) {
	return str.charAt(0).toUpperCase() + str.slice(1)
}

export function getSubstringBetween(str: string, start: string, end: string): string | undefined {
	const startIndex = str.indexOf(start)
	if (startIndex === -1) {
		return
	}

	const endIndex = str.indexOf(end, startIndex + start.length)
	if (endIndex === -1) {
		return
	}

	return str.substring(startIndex + start.length, endIndex)
}
