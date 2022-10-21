import type { RouterContext, HybridRequestOptions } from '@hybridly/core'
import { router, registerHook } from '@hybridly/core'
import { state } from '../stores/state'

type BackForwardCallback = (context: RouterContext) => void

export function useBackForward() {
	const callbacks: BackForwardCallback[] = []

	// On navigation events, if the navigation is a back/forward
	// navigation, call the registered callbacks.
	registerHook('navigated', (options) => {
		if (options.isBackForward) {
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

	return {
		onBackForward,
		reloadOnBackForward,
	}
}
