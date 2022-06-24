import { RouterContext, VisitOptions } from 'sleightful'
import { router } from '../router'
import { state } from '../stores/state'

type BackForwardCallback = (context: RouterContext) => void

export function useBackForward() {
	const callbacks: BackForwardCallback[] = []

	// On navigation events, if the navigation is a back/forward
	// visit, call the registered callbacks.
	state.context.value?.events.on('navigate', (options) => {
		if (options.isBackForward) {
			callbacks.forEach((fn) => fn(state.context.value!))
			callbacks.splice(0, callbacks.length)
		}
	})

	/**
	 * Applies the given callback on back-forward visits.
	 */
	function onBackForward(fn: BackForwardCallback) {
		callbacks.push(fn)
	}

	/**
	 * Reloads the page on back-forward visits.
	 */
	function reloadOnBackForward(options?: VisitOptions) {
		onBackForward(() => router.reload(options))
	}

	return {
		onBackForward,
		reloadOnBackForward,
	}
}
