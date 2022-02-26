/*
|--------------------------------------------------------------------------
| View
|--------------------------------------------------------------------------
*/

/** A page or dialog component. */
export interface View {
	/** Name of the component to use. */
	name: string
	/** Properties to pass to the component. */
	properties: Properties
}

export type Property = null | string | number | boolean | Property[] | { [name: string]: Property }
export type Properties = Record<string | number, Property>

/*
|--------------------------------------------------------------------------
| Swap
|--------------------------------------------------------------------------
*/

export interface SwapOptions<T> {
	/** The new component. */
	component: T
	/** Whether to preserve the state of the component. */
	preserveState?: boolean
}

export type ViewComponent = any
export type DialogComponent = any
export type ResolveComponent = (name: string) => Promise<ViewComponent>
export type SwapView = (options: SwapOptions<ViewComponent>) => Promise<void>
export type SwapDialog = (options: SwapOptions<DialogComponent>) => Promise<void>

/*
|--------------------------------------------------------------------------
| Request
|--------------------------------------------------------------------------
*/

/** The payload of a visit request from the server. */
export interface VisitPayload {
	/** The view to use in this request. */
	view: View
	/** An optional dialog. */
	dialog?: View
	/** The current page URL. */
	url: string
	/** The current asset version. */
	version: string
}

export type RequestPayload = FormDataValue | FormData
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
