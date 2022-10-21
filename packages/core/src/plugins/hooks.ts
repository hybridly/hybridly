import type { AxiosResponse } from 'axios'
import type { InternalRouterContext } from '../context'
import { getRouterContext } from '../context'
import type { NavigationOptions, HybridRequestOptions, Errors, Progress, HybridPayload } from '../router'
import type { MaybePromise } from '../types'

export interface Hooks {
	/**
	 * Called before anything when a navigation is going to happen.
	 */
	before: (options: HybridRequestOptions) => MaybePromise<any | boolean>

	/**
	 * Called before the request of a navigation is going to happen.
	 */
	start: (context: InternalRouterContext) => MaybePromise<any>

	/**
	 * Called when progress on the request is being made.
	 */
	progress: (progress: Progress) => MaybePromise<any>

	/**
	 * Called when data is received after a request for a navigation.
	 */
	data: (response: AxiosResponse) => MaybePromise<any>

	/**
	 * Called when a request is successful and there is no error.
	 */
	success: (payload: HybridPayload) => MaybePromise<any>

	/**
	 * Called when a request is successful but there were errors.
	 */
	error: (errors: Errors) => MaybePromise<any>

	/**
	 * Called when a request has been aborted.
	 */
	abort: (context: InternalRouterContext) => MaybePromise<any>

	/**
	 * Called when a response to a request is not a valid hybrid response.
	 */
	invalid: (response: AxiosResponse) => MaybePromise<void>

	/**
	 * Called when an unknowne exception was triggered.
	 */
	exception: (error: Error) => MaybePromise<void>

	/**
	 * Called whenever the request failed, for any reason, in addition to other hooks.
	 */
	fail: (context: InternalRouterContext) => MaybePromise<void>

	/**
	 * Called after a request has been made, even if it didn't succeed.
	 */
	after: (context: InternalRouterContext) => MaybePromise<void>

	/**
	 * Called when a navigation has been made and a page component has been navigated to.
	 */
	navigated: (options: NavigationOptions) => MaybePromise<void>
}

interface HookOptions {
	/** Executes the hook only once. */
	once?: boolean
}

/**
 * Registers a global hook.
 */
export function appendCallbackToHooks<T extends keyof Hooks>(hook: T, fn: Hooks[T]): () => void {
	const hooks = getRouterContext().hooks

	hooks[hook] = [...(hooks[hook] ?? []), fn] as Hooks[T][]

	return () => hooks[hook]?.splice(hooks[hook]!.indexOf(fn), 1)
}

/**
 * Registers a global hook.
 */
export function registerHook<T extends keyof Hooks>(hook: T, fn: Hooks[T], options?: HookOptions): () => void {
	if (options?.once) {
		const unregister = appendCallbackToHooks(hook, async(...args: [any]) => {
			await fn(...args)
			unregister()
		})

		return unregister
	}

	return appendCallbackToHooks(hook, fn)
}
