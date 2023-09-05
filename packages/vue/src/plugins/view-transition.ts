import { type Plugin } from '@hybridly/core'

export function viewTransition(): Plugin {
	if (!document.startViewTransition) {
		return { name: 'view-transition' }
	}

	let domUpdated: undefined | (() => void)

	return {
		name: 'view-transition',
		navigating: async({ isInitial }) => {
			if (isInitial) {
				return
			}

			// eslint-disable-next-line promise/param-names
			return new Promise((confirmTransitionStarted) => document.startViewTransition!(() => {
				confirmTransitionStarted(true)
				return new Promise<void>((resolve) => domUpdated = resolve)
			}))
		},
		mounted: () => {
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
