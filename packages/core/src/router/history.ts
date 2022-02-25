import { RouterContext } from './context'

/** Puts the given context into the history state. */
export function setHistoryState(context: RouterContext, options: HistoryOptions = {}) {
	if (!window?.history) {
		throw new Error('The history API is not available, so Sleightful cannot operate.')
	}

	const method = options.replace
		? 'replaceState'
		: 'pushState'

	window.history[method](context, '', context.url)
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
