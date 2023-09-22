import { debug } from '@hybridly/utils'
import { SCROLL_REGION_ATTRIBUTE } from './constants'
import { getRouterContext, setContext } from './context'
import { setHistoryState } from './router/history'

/** Saves the current view's scrollbar positions into the history state. */
export function saveScrollPositions(): void {
	const regions = getScrollRegions()
	debug.scroll('Saving scroll positions of:', regions.map((el) => ({ el, scroll: { top: el.scrollTop, left: el.scrollLeft } })))

	// The context stores the scroll positions. Upon saving, we need to
	// update it first.
	setContext({
		scrollRegions: regions.map(({ scrollTop, scrollLeft }) => ({
			top: scrollTop,
			left: scrollLeft,
		})),
	})

	// After context is updated, we can save it to the history state,
	// which is used upon scroll position restoration.
	setHistoryState({ replace: true })
}

/** Gets DOM elements which scroll positions should be saved. */
export function getScrollRegions(): Element[] {
	return Array.from(document?.querySelectorAll(`[${SCROLL_REGION_ATTRIBUTE}]`) ?? [])
		.concat(document.documentElement, document.body)
}

/**
 * Resets the current view's scrollbars positions to the top, and save them
 * in the history state.
 */
export function resetScrollPositions(): void {
	debug.scroll('Resetting scroll positions.')
	getScrollRegions().forEach((element) => element.scrollTo({
		top: 0,
		left: 0,
	}))

	saveScrollPositions()

	// If there is a hash and an element with that hash, we want to
	// scroll to that element in order to imitate native browser behavior.
	if (window.location.hash) {
		debug.scroll(`Hash is present, scrolling to the element of ID ${window.location.hash}.`)
		document.getElementById(window.location.hash.slice(1))?.scrollIntoView()
	}
}

/** Restores the scroll positions stored in the context. */
export async function restoreScrollPositions(): Promise<void> {
	const context = getRouterContext()
	const regions = getScrollRegions()

	if (!context.scrollRegions) {
		debug.scroll('No region found to restore.')
		return
	}

	context.adapter.executeOnMounted(() => {
		debug.scroll(`Restoring ${regions.length}/${context.scrollRegions.length} region(s).`)
		regions.forEach((el: Element, i) => el.scrollTo({
			top: context.scrollRegions.at(i)?.top ?? el.scrollTop,
			left: context.scrollRegions.at(i)?.top ?? el.scrollLeft,
		}))
	})
}
