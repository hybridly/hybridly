import type { DialogVisit, ExternalVisitOptions, NavigationOptions, ResolveComponent, RouterOptions, StatefulVisit, SwapDialog, SwapView, Visit } from '../types'
import { match } from '../utils'
import { getCurrentVisitType, makeUrl, STORAGE_EXTERNAL_KEY } from './utils'

/**
 * Initializes state for a visit.
 */
function statefulVisit(visit: Visit, state: Partial<StatefulVisit> = {}): StatefulVisit {
	return {
		visit,
		id: '',
		scrollPositions: [],
		state: {},
		...state,
	}
}

export class Router {
	public id: string
	protected resolveComponent: ResolveComponent
	protected swapView: SwapView
	protected swapDialog: SwapDialog
	protected currentState: StatefulVisit

	constructor(options: RouterOptions) {
		this.id = ''
		this.swapView = options.swap.view
		this.swapDialog = options.swap.dialog
		this.resolveComponent = options.resolve
		this.currentState = statefulVisit(options.visit)
		// this.events.emit('navigate', this.currentState.visit.view)
	}

	async initialize() {
		await match(getCurrentVisitType(), {
			back_forward: async() => await this.handleBackForwardVisit(),
			external: async() => await this.handleExternalVisit(),
			sleightful: async() => await this.handleVisit(),
		})
	}

	/*
	|--------------------------------------------------------------------------
	| Visit handling
	|--------------------------------------------------------------------------
	*/

	private async handleExternalVisit(): Promise<void> {
		const options = JSON.parse(window.sessionStorage.getItem(STORAGE_EXTERNAL_KEY) || '{}') as ExternalVisitOptions
		window.sessionStorage.removeItem(STORAGE_EXTERNAL_KEY)

		await this.navigate({
			visit: this.currentState.visit,
			preserveScroll: options.preserveScroll,
			preserveState: true,
		})
	}

	private async handleBackForwardVisit(): Promise<void> {
		window.history.state.version = this.currentState.visit.version

		await this.navigate({
			visit: window.history.state,
			preserveScroll: true,
			preserveState: true,
		})

		this.restoreScrollPositions()
	}

	private async handleVisit(): Promise<void> {
		await this.navigate({
			visit: this.currentState.visit,
			preserveState: true,
		})
	}

	/*
	|--------------------------------------------------------------------------
	| Core navigation
	|--------------------------------------------------------------------------
	*/

	async navigate(options: NavigationOptions): Promise<void> {
		const visit = options.visit as DialogVisit

		// TODO: preserve hash
		// window.location.hash - not sure where the logic should belong

		const shouldReplace = options.replace || makeUrl(visit.dialog?.url ?? visit.view?.url).href === window.location.href
		const pageComponent = await this.resolveComponent(visit.view.name)
		const dialogComponent = options.visit.type === 'dialog'
			? await this.resolveComponent(visit.dialog.name)
			: undefined

		// The view state needs to be preserved if we have a dialog.
		// Otherwise, we preserve it only when specified.
		await this.swapView({
			view: visit.view,
			component: pageComponent,
			preserveState: dialogComponent ? true : options.preserveState,
		})

		await this.swapDialog({
			component: dialogComponent,
			preserveState: options.preserveState,
		})

		this.setHistoryState(options.visit, shouldReplace)

		if (options.preserveScroll) {
			this.restoreScrollPositions()
		}

		if (!shouldReplace) {
			// this.events.emit('navigate', this)
		}
	}

	// private setupEventListeners(): void {
	// 	window.addEventListener('popstate', this.handlePopstateEvent.bind(this))
	// 	document.addEventListener('scroll', debounce(this.handleScrollEvent.bind(this), 100), true)
	// }

	/*
	|--------------------------------------------------------------------------
	| Scroll regions
	|--------------------------------------------------------------------------
	*/

	/**
	 * Gets the elements which scroll positions should be preserved.
	 */
	scrollRegions() {
		return document.querySelectorAll('[scroll-region]')
	}

	async restoreScrollPositions(): Promise<void> {
		const positions = this.scrollRegions()

		if (!this.currentState.scrollPositions) {
			return
		}

		const timer = setInterval(() => {
			// TODO: find a better way to ensure the page component is fully loaded
			if (this.currentState.scrollPositions.length !== positions.length) {
				console.log('[sleightful] waiting for component to load')
				return
			}

			clearInterval(timer)
			positions.forEach((el: Element, i) => {
				el.scrollTop = this.currentState.scrollPositions.at(i)?.top ?? el.scrollTop
				el.scrollLeft = this.currentState.scrollPositions.at(i)?.left ?? el.scrollLeft
			})
		}, 10)
	}

	/*
	|--------------------------------------------------------------------------
	| History state
	|--------------------------------------------------------------------------
	*/

	/**
	 * Updates the history state for the given view.
	 */
	protected setHistoryState(visit: Visit, replace: boolean): void {
		// TODO - maybe try to move
		this.currentState.visit = visit

		window.history[replace ? 'replaceState' : 'pushState'](
			visit, // data for this history entry
			'', // this is unused, great api design haha
			visit.type === 'dialog' ? visit.dialog.url : visit.view.url, // url of this history entry
		)
	}
}

/**
 * Initializes the sleightful router.
 */
export async function initialize(options: RouterOptions): Promise<Router> {
	const router = new Router(options)
	await router.initialize()

	return router
}
