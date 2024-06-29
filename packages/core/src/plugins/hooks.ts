/* eslint-disable jsdoc/check-alignment */
import type { AxiosResponse } from 'axios'
import type { InternalRouterContext } from '../context'
import { getRouterContext } from '../context'
import type { Errors, HybridPayload, HybridRequestOptions, InternalNavigationOptions, Progress } from '../router'
import type { MaybePromise } from '../types'

// #region requesthooks
export interface RequestHooks {
/* [!code focus:54] */	/**
* Called before a navigation request is going to happen.
*/
	before: (options: HybridRequestOptions, context: InternalRouterContext) => MaybePromise<any | boolean>

	/**
	 * Called before the request of a navigation is going to happen.
	 */
	start: (context: InternalRouterContext) => MaybePromise<any>

	/**
	 * Called when progress on the request is being made.
	 */
	progress: (progress: Progress, context: InternalRouterContext) => MaybePromise<any>

	/**
	 * Called when data is received after a request for a navigation.
	 */
	data: (response: AxiosResponse, context: InternalRouterContext) => MaybePromise<any>

	/**
	 * Called when a request is successful and there is no error.
	 */
	success: (payload: HybridPayload, context: InternalRouterContext) => MaybePromise<any>

	/**
	 * Called when a request is successful but there were errors.
	 */
	error: (errors: Errors, context: InternalRouterContext) => MaybePromise<any>

	/**
	 * Called when a request has been aborted.
	 */
	abort: (context: InternalRouterContext) => MaybePromise<any>

	/**
	 * Called when a response to a request is not a valid hybrid response.
	 */
	invalid: (response: AxiosResponse, context: InternalRouterContext) => MaybePromise<any>

	/**
	 * Called when an unknowne exception was triggered.
	 */
	exception: (error: Error, context: InternalRouterContext) => MaybePromise<any>

	/**
	 * Called whenever the request failed, for any reason, in addition to other hooks.
	 */
	fail: (context: InternalRouterContext) => MaybePromise<any>

	/**
	 * Called after a request has been made, even if it didn't succeed.
	 */
	after: (context: InternalRouterContext) => MaybePromise<any>
}
// #endregion requesthooks

// #region hooks
export interface Hooks extends RequestHooks {
/* [!code focus:28] */	/**
/////////////////////// * Called when Hybridly's context is initialized.
///////////////////////
///////////////////////
///////////////////////
///////////////////////
///////////////////////
///////////////////////
///////////////////////
///////////////////////
///////////////////////
///////////////////////
*/
	initialized: (context: InternalRouterContext) => MaybePromise<any>

	/**
	 * Called after Hybridly's initial load.
	 */
	ready: (context: InternalRouterContext) => MaybePromise<any>

	/**
	 * Called when a back-forward navigation occurs.
	 */
	backForward: (state: any, context: InternalRouterContext) => MaybePromise<any>

	/**
	 * Called when a component navigation is being made.
	 */
	navigating: (options: InternalNavigationOptions, context: InternalRouterContext) => MaybePromise<any>

	/**
	 * Called when a component has been navigated to.
	 */
	navigated: (options: InternalNavigationOptions, context: InternalRouterContext) => MaybePromise<any>

	/**
	 * Called when a component has been navigated to and was mounted by the adapter.
	 */
	mounted: (options: InternalNavigationOptions & MountedHookOptions, context: InternalRouterContext) => MaybePromise<any>
}
// #endregion hooks

export interface MountedHookOptions {
	/**
	 * Whether the component being mounted is a dialog.
	 */
	isDialog: boolean
}

export interface HookOptions {
	/** Executes the hook only once. */
	once?: boolean
}

/**
 * Registers a global hook.
 */
export function appendCallbackToHooks<T extends keyof Hooks>(hook: T, fn: Hooks[T]): () => void {
	const hooks = getRouterContext().hooks

	hooks[hook] = [...(hooks[hook] ?? []), fn] as Hooks[T][]

	return () => {
		const index = hooks[hook]!.indexOf(fn)

		if (index !== -1) {
			hooks[hook]?.splice(index, 1)
		}
	}
}

/**
 * Registers a global hook.
 */
export function registerHook<T extends keyof Hooks>(hook: T, fn: Hooks[T], options?: HookOptions): () => void {
	if (options?.once) {
		const unregister = appendCallbackToHooks(hook, async (...args: any[]) => {
			await fn(...args as [any, any])
			unregister()
		})

		return unregister
	}

	return appendCallbackToHooks(hook, fn)
}
