import { debounce, debug } from '@hybridly/utils'
import { SCROLL_REGION_ATTRIBUTE } from '../constants'
import { navigate } from './router'
import { RouterContext, RouterContextOptions, Serializer, setContext } from './context'
import { saveScrollPositions } from './scroll'
import { makeUrl } from './url'

type SerializedContext = Omit<RouterContext, 'adapter' | 'events' | 'serializer'>

/** Puts the given context into the history state. */
export function setHistoryState(context: RouterContext, options: HistoryOptions = {}) {
	if (!window?.history) {
		throw new Error('The history API is not available, so Hybridly cannot operate.')
	}

	const method = options.replace
		? 'replaceState'
		: 'pushState'

	const serialized = serializeContext(context)

	debug.history('Setting history state:', {
		method,
		context,
		serialized,
	})

	try {
		window.history[method](serialized, '', context.url)
	} catch (error) {
		console.error('Hybridly could not save its current state in the history. This is most likely due to a property being non-serializable, such as a proxy or a reference.')
		throw error
	}
}

/** Gets the current history state if it exists. */
export function getHistoryState<T = any>(context: RouterContext, key?: string): T {
	const state = key
		? window.history.state?.state?.[key]
		: window.history.state?.state

	return context.serializer.unserialize<T>(state)
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

		// If the history entry has been hybridlyly tempered with, we want
		// to use it. We swap the components accordingly.
		await navigate(context, {
			payload: event.state,
			preserveScroll: true,
			preserveState: false,
			updateHistoryState: false,
			isBackForward: true,
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

/** Saves a value into the current history state. */
export function remember<T = any>(context: RouterContext, key: string, value: T): void {
	debug.history(`Remembering key "${key}" with value`, value)

	setContext(context, {
		state: {
			...context.state,
			[key]: value,
		},
	})

	setHistoryState(context, { replace: true })
}

/** Gets a value saved into the current history state. */
export function getKeyFromHistory<T = any>(context: RouterContext, key: string): T {
	return getHistoryState<T>(context, key)
}

/** Serializes the context so it can be written to the history state. */
export function serializeContext(context: RouterContext): SerializedContext {
	return {
		url: context.url,
		version: context.version,
		view: context.serializer.serialize(context.view),
		dialog: context.dialog,
		scrollRegions: context.scrollRegions,
		state: context.serializer.serialize(context.state),
	}
}

export function createSerializer(options: RouterContextOptions): Serializer {
	if (options.serializer) {
		return options.serializer
	}

	return {
		serialize: (view) => JSON.parse(JSON.stringify(view)),
		unserialize: (state) => state,
	}
}

export interface HistoryOptions {
	/**
	 * Replace the current history entry instead of pushing a new entry.
	 * @default false
	 */
	replace?: boolean
}
