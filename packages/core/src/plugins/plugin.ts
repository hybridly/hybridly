import { debug } from '@monolikit/utils'
import type { InternalRouterContext } from '../context'
import { getRouterContext } from '../context'
import type { MaybePromise } from '../types'
import type { Hooks } from './hooks'

export interface Plugin {
	name: string
	initialized: (context: InternalRouterContext) => MaybePromise<void>
	hooks: Partial<Hooks>
}

export function definePlugin(plugin: Plugin): Plugin {
	return plugin
}

export async function runPluginHooks<T extends keyof Hooks>(hook: T, ...args: Parameters<Hooks[T]>): Promise<boolean> {
	const { plugins } = getRouterContext()

	let result = true
	for (const plugin of plugins) {
		if (plugin.hooks[hook]) {
			debug.plugin(plugin.name, `Calling "${hook}" hooks.`)
			result = await plugin.hooks[hook]?.(...args as [any]) ?? result
		}
	}

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
		requestHooks?.[hook]?.(...args as [any]),
		runGlobalHooks(hook, ...args),
		runPluginHooks(hook, ...args),
	])

	return !result.includes(false)
}
