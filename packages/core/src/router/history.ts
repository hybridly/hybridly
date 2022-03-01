import { SCROLL_REGION_ATTRIBUTE } from '../constants'
import { debounce, debug } from '../utils'
import { navigate } from './router'
import { RouterContext } from './context'
import { saveScrollPositions } from './scroll'
import { makeUrl } from './url'

type SerializedContext = Omit<RouterContext, 'adapter' | 'events'>

/** Puts the given context into the history state. */
export function setHistoryState(context: RouterContext, options: HistoryOptions = {}) {
	if (!window?.history) {
		throw new Error('The history API is not available, so Sleightful cannot operate.')
	}

	const method = options.replace
		? 'replaceState'
		: 'pushState'

	debug.history('Setting history state:', { method, context })

	try {
		window.history[method](serializeContext(context), '', context.url)
	} catch (error) {
		console.error('Sleightful could not save its current state in the history. This is most likely due to a property being non-serializable, such as a proxy or a reference.')
		throw error
	}
}

/** Gets the current history state if it exists. */
export function getHistoryState<T extends keyof HistoryState | undefined>(
	key?: T,
): T extends keyof HistoryState ? HistoryState[T] : HistoryState | undefined {
	if (window?.history.state) {
		return key
			? window.history.state[key]
			: window.history.state
	}

	return key
		? undefined as any
		: { state: {} }
}

/** Register history-related event listeneners. */
export async function registerEventListeners(context: RouterContext) {
	debug.history('Registering [popstate] and [scroll] event listeners.')

	// Popstate is for catching back and forward navigations. We want
	// to imitate native browser behavior while keeping the SPA feeling.
	window?.addEventListener('popstate', async(event) => {
		debug.history('Navigation detected (popstate event). State:', { state: event.state })

		// If there is no state in this history entry, we come from the user
		// replacing the URL manually. In this case, we want to restore everything
		// like it was. We need to copy the hash if any and restore the scroll positions.
		if (!event.state) {
			debug.history('There is no state. Adding hash if any and restoring scroll positions.')

			return await navigate(context, {
				payload: {
					...context,
					url: makeUrl(context.url, { hash: window.location.hash }).toString(),
				},
				preserveScroll: true,
				preserveState: true,
				replace: true,
			})
		}

		// If the history entry has been sleightfully tempered with, we want
		// to use it. We swap the components accordingly.
		await navigate(context, {
			payload: event.state,
			preserveScroll: true,
			preserveState: false,
			updateHistoryState: false,
		})
	})

	// On scroll, we want to save the positions of all scrollbars.
	// This is needed in order to restore them upon navigation.
	window?.addEventListener('scroll', (event) => debounce(() => {
		if ((event?.target as Element)?.hasAttribute?.(SCROLL_REGION_ATTRIBUTE)) {
			saveScrollPositions(context)
		}
	}, 100), true)
}

/** Checks if the current visit was made by going back or forward. */
export function isBackForwardVisit(): boolean {
	if (!window.history.state) {
		return false
	}

	return (window.performance?.getEntriesByType('navigation').at(0) as PerformanceNavigationTiming)?.type === 'back_forward'
}

/** Handles a visit which was going back or forward. */
export async function handleBackForwardVisit(context: RouterContext): Promise<void> {
	debug.router('Handling a back/forward visit.')
	window.history.state.version = context.version

	await navigate(context, {
		preserveScroll: true,
		preserveState: true,
	})
}

/** Serializes the context so it can be written to the history state. */
export function serializeContext(context: RouterContext): SerializedContext {
	return {
		url: context.url,
		version: context.version,
		view: context.view,
		dialog: context.dialog,
		scrollRegions: context.scrollRegions,
	}
}

export interface HistoryOptions {
	/**
	 * Replace the current history entry instead of pushing a new entry.
	 * @default false
	 */
	replace?: boolean
}

export interface HistoryState {
	state: any
}
