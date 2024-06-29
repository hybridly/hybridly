import { type Plugin } from '@hybridly/core'

export function viewTransition(): Plugin {
	if (!document.startViewTransition) {
		return { name: 'view-transition' }
	}

	let domUpdated: undefined | (() => void)

	return {
		name: 'view-transition',
		navigating: async ({ type, hasDialog }) => {
			if (type === 'initial' || hasDialog) {
				return
			}

			return new Promise((confirmTransitionStarted) => document.startViewTransition!(() => {
				confirmTransitionStarted(true)
				return new Promise<void>((resolve) => domUpdated = resolve)
			}))
		},
		mounted: () => {
			domUpdated?.()
			domUpdated = undefined
		},
		navigated: () => {
			// Just in case the `mounted` hook couldn't be called,
			// we clean up the promise to avoid a ~4s hang
			domUpdated?.()
			domUpdated = undefined
		},
	}
}

declare global {
	interface Document {
		startViewTransition?: (callback: () => Promise<void>) => {
			finished: Promise<void>
			updateCallbackDone: Promise<void>
			ready: Promise<void>
		}
	}
}
