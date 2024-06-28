import type { HybridRequestOptions, RouterContext } from '@hybridly/core'
import { registerHook, router } from '@hybridly/core'
import { state } from '../stores/state'

type BackForwardCallback = (context: RouterContext) => void

interface UseBackForwardOptions {
	/**
	 * Calls `reloadOnBackForward` immediately.
	 */
	reload: boolean | HybridRequestOptions
}

export function useBackForward(options?: UseBackForwardOptions) {
	const callbacks: BackForwardCallback[] = []

	// On navigation events, if the navigation is a back/forward
	// navigation, call the registered callbacks.
	registerHook('navigated', (options) => {
		if (options.type === 'back-forward') {
			callbacks.forEach((fn) => fn(state.context.value!))
			callbacks.splice(0, callbacks.length)
		}
	})

	/**
	 * Applies the given callback on back-forward navigations.
	 */
	function onBackForward(fn: BackForwardCallback) {
		callbacks.push(fn)
	}

	/**
	 * Reloads the page on back-forward navigations.
	 */
	function reloadOnBackForward(options?: HybridRequestOptions) {
		onBackForward(() => router.reload(options))
	}

	if (options?.reload) {
		reloadOnBackForward(options.reload === true ? undefined : options.reload)
	}

	return {
		onBackForward,
		reloadOnBackForward,
	}
}
