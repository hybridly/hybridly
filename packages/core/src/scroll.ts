import { debug } from '@monolikit/utils'
import { SCROLL_REGION_ATTRIBUTE } from './constants'
import { getRouterContext, setContext } from './context'
import { setHistoryState } from './router/history'

/** Saves the current page's scrollbar positions into the history state. */
export function saveScrollPositions(): void {
	const regions = getScrollRegions()
	debug.scroll('Saving scroll positions of:', regions)

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
}

/**
 * Resets the current page's scrollbars positions to the top, and save them
 * in the history state.
 */
export function resetScrollPositions(): void {
	debug.scroll('Resetting scroll positions.')
	getScrollRegions()
		.concat(document.documentElement, document.body)
		.forEach((element) => {
			element.scrollTop = 0
			element.scrollLeft = 0
		})

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
	debug.scroll('Restoring scroll positions stored in the context.')
	const context = getRouterContext()
	const regions = getScrollRegions()

	if (!context.scrollRegions) {
		return
	}

	// There is a case where the page has not fully loaded when restoring scroll positions.
	// We can detect this by comparing the stored scroll regions with the detected ones.
	// If the count does not match, we wait a few milliseconds before retrying.
	let tries = 0
	const timer = setInterval(() => {
		if (context.scrollRegions.length !== regions.length) {
			if (++tries > 20) {
				debug.scroll('The limit of tries has been reached. Cancelling scroll restoration.')
				clearInterval(timer)
				return
			}

			debug.scroll(`The scroll regions count do not match. Waiting for page to fully load (try #${tries}).`)
			return
		}

		clearInterval(timer)
		regions.forEach((el: Element, i) => el.scrollTo({
			top: context.scrollRegions.at(i)?.top ?? el.scrollTop,
			left: context.scrollRegions.at(i)?.top ?? el.scrollLeft,
		}))
	}, 50)
}
