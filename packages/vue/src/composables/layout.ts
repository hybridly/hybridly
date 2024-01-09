import { state } from '../stores/state'

export type Layout = any

/**
 * Sets the persistent layout for this view.
 */
export function defineLayout<T extends Record<string, K>, K = any>(layout: Layout, properties?: T): void
export function defineLayout(layouts: Layout[]): void
export function defineLayout(...args: any[]): void {
	const layouts = args[0] as Layout | Layout[]
	const properties = args[1] as any | undefined
	state.setViewLayout(layouts, properties)
}

/**
 * Sets or gets the properties for the current persistent layout.
 */
export function defineLayoutProperties<T extends Record<string, K>, K = any>(properties: T): void {
	state.setViewLayoutProperties(properties)
}
