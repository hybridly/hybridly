import { type Plugin } from '@hybridly/core'

export function viewTransition(): Plugin {
	if (!document.startViewTransition) {
		return { name: 'view-transition' }
	}

	let domUpdated: undefined | (() => void)

	return {
		name: 'view-transition',
		navigating: async ({ type, hasDialog, viewTransition }) => {
			if (type === 'initial' || hasDialog || viewTransition === false) {
				return
			}

			return new Promise((confirmTransitionStarted) =>
				document.startViewTransition({
					update: () => {
						confirmTransitionStarted(true)
						return new Promise<void>((resolve) => domUpdated = resolve)
					},
					types: getViewTransitionType(viewTransition),
				})
			)
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

function getViewTransitionType(viewTransition?: string | boolean | string[]): null | string[] {
	if (viewTransition === false || viewTransition === undefined || viewTransition === true) {
		return null
	}

	if (Array.isArray(viewTransition)) {
		return viewTransition
	}

	return [viewTransition]
}
