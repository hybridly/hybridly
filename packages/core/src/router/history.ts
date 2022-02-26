import { navigate } from '..'
import { debug } from '../utils'
import { RouterContext } from './context'

type SerializedContext = Omit<RouterContext, 'adapter'>

/** Puts the given context into the history state. */
export function setHistoryState(context: RouterContext, options: HistoryOptions = {}) {
	if (!window?.history) {
		throw new Error('The history API is not available, so Sleightful cannot operate.')
	}

	const method = options.replace
		? 'replaceState'
		: 'pushState'

	debug.history('Setting history state:', { method, context })

	window.history[method](serializeContext(context), '', context.url)
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

/** Checks if the current visit was made by going back or forward. */
export function isBackForwardVisit(): boolean {
	if (!window.history.state) {
		return false
	}

	return (window.performance?.getEntriesByType('navigation').at(0) as PerformanceNavigationTiming)?.type === 'back_forward'
}

/** Handles a visit which was going back or forward. */
export async function handleBackForwardVisit(context: RouterContext): Promise<void> {
	window.history.state.version = context.version

	await navigate(context, {
		preserveScroll: true,
		preserveState: true,
	})

	// restoreScrollPositions()
}

/** Serializes the context so it can be written to the history state. */
export function serializeContext(context: RouterContext): SerializedContext {
	return {
		url: context.url,
		version: context.version,
		view: context.view,
		dialog: context.dialog,
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
