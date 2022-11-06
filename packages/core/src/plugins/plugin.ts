import { debug } from '@hybridly/utils'
import type { InternalRouterContext } from '../context'
import { getRouterContext } from '../context'
import type { MaybePromise } from '../types'
import type { Hooks } from './hooks'

export interface Plugin extends Partial<Hooks> {
	name: string
	initialized?: (context: InternalRouterContext) => MaybePromise<void>
}

export function definePlugin(plugin: Plugin): Plugin {
	return plugin
}

export async function forEachPlugin(cb: (plugin: Plugin) => MaybePromise<any>) {
	const { plugins } = getRouterContext()

	for (const plugin of plugins) {
		await cb(plugin)
	}
}

export async function runPluginHooks<T extends keyof Hooks>(hook: T, ...args: Parameters<Hooks[T]>): Promise<boolean> {
	let result = true

	await forEachPlugin(async(plugin) => {
		if (plugin[hook]) {
			debug.plugin(plugin.name, `Calling "${hook}" hook.`)
			result &&= await plugin[hook]?.(...args as [any, any]) !== false
		}
	})

	return result
}

export async function runGlobalHooks<T extends keyof Hooks>(hook: T, ...args: Parameters<Hooks[T]>): Promise<boolean> {
	const { hooks } = getRouterContext()

	if (!hooks[hook]) {
		return true
	}

	let result = true
	for (const fn of hooks[hook]!) {
		debug.hook(`Calling global "${hook}" hooks.`)
		result = await fn(...args as [any]) ?? result
	}

	return result
}

export async function runHooks<T extends keyof Hooks>(
	hook: T,
	requestHooks?: Partial<Hooks>,
	...args: Parameters<Hooks[T]>
): Promise<boolean> {
	const result = await Promise.all<boolean>([
		requestHooks?.[hook]?.(...args as [any, any]),
		runGlobalHooks(hook, ...args),
		runPluginHooks(hook, ...args),
	])

	debug.hook(`Called all hooks for [${hook}],`, result)

	return !result.includes(false)
}
