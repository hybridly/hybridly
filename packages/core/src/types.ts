/*
|--------------------------------------------------------------------------
| Router
|--------------------------------------------------------------------------
*/

export interface RouterOptions {
	/**
	 * The initial visit.
	 */
	visit: Visit

	/**
	 * Resolves a component from the given name.
	 */
	resolve: ResolveComponent

	swap: {
		/**
		 * Applies the given dialog component.
		 */
		dialog: SwapDialog

		/**
		 * DApplies the given view component.
		 */
		view: SwapView
	}
}

export interface SwapOptions<T> {
	component: T
	preserveState?: boolean
}

export type ViewComponent = any
export type DialogComponent = any
export type ResolveComponent = (name: string) => ViewComponent
export type SwapView = (options: SwapOptions<ViewComponent> & { view: View }) => Promise<void>
export type SwapDialog = (options: SwapOptions<DialogComponent>) => Promise<void>

/*
|--------------------------------------------------------------------------
| View
|--------------------------------------------------------------------------
*/

export type Property = null | string | number | boolean | Property[] | { [name: string]: Property }
export type Properties = Record<string, Property>

/**
 * A view component, which can be accessed from an URL.
 */
export interface View {
	/**
	 * Name of the page component.
	 */
	name: string

	/**
	 * Properties applied on the page component.
	 */
	properties: Properties

	/**
	 * URL of the page component.
	 */
	url: string
}

/*
|--------------------------------------------------------------------------
| Responses - they are messages sent by the back-end adapter
|--------------------------------------------------------------------------
*/

export interface DialogVisit {
	/**
	 * A visit to a dialog page, which have an underlying page.
	 */
	type: 'dialog'

	/**
	 * The base view for this visit.
	 */
	view: View

	/**
	 * The view for the dialog.
	 */
	dialog: View & {
		/**
		 * TODO: find out
		 */
		eager: boolean
	}
}

export interface PageVisit {
	/**
	 * A visit to a full page.
	 */
	type: 'page'

	/**
	 * The view for this visit.
	 */
	view: View
}

/**
 * The shape of the data that the application layers sends to the view layer.
 */
export type Visit = (DialogVisit | PageVisit) & {
	/**
	 * The current asset version.
	 */
	version?: string

	/**
	 * TODO: find out
	 */
	context: string
}

/**
 * Visit with a view and a history state.
 */
export interface StatefulVisit {
	id: string
	visit: Visit
	scrollPositions: ScrollPosition[]
	state: Record<string, any>
}

/*
|--------------------------------------------------------------------------
| Requests - they are messages sent from the front-end adapter
|--------------------------------------------------------------------------
*/

export type RequestData = FormDataValue | FormData
export interface FormData {
	[Symbol.iterator](): IterableIterator<[string, FormDataValue]>
	entries(): IterableIterator<[string, FormDataValue]>
	keys(): IterableIterator<string>
	values(): IterableIterator<FormDataValue>
}

type FormDataObject = { [Key in string]: FormDataValue }
type FormDataPrimitive = Blob | Date | boolean | number | File | string | null
type FormDataArray = FormDataValue[]
type FormDataValue = FormDataObject | FormDataPrimitive | FormDataArray

export interface NavigationOptions {
	/**
	 * View to navigate to.
	 */
	visit: Visit

	/**
	 * Whether to replace the current history state instead of adding
	 * one. This affects the browser's "back" and "forward" features.
	 */
	replace?: boolean

	/**
	 * Whether to preserve the current scrollbar position.
	 */
	preserveScroll?: boolean

	/**
	 * Whether to preserve the current page component state.
	 */
	preserveState?: boolean

	/**
	 * Specific headers to add to the request.
	 */
	headers?: Record<string, string>

	/**
	 * The bag in which to put potential errors.
	 */
	errorBag?: string
}

export interface VisitRequest {
	/**
	 * HTTP verb to use for this request.
	 */
	method: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE'

	/**
	 * Body of the request.
	 */
	data: RequestData

	/**
	 * Which properties to update for this visit. Other properties will be ignored.
	 */
	only: Property[] | Properties

	/**
	 * Which properties not to update for this visit. Other properties will be updated.
	 */
	except: Property[] | Properties
}

/*
|--------------------------------------------------------------------------
*/

export interface VisitOptions {

}

export interface ExternalVisitOptions {
	preserveScroll: boolean
}

export interface ScrollPosition {
	top: number
	left: number
}
