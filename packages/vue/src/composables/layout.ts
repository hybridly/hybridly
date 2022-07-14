import { ComponentOptions } from 'vue'
import { state } from '../stores/state'

export type Layout = ComponentOptions | (() => ComponentOptions) | [ComponentOptions]

/**
 * Sets the persistent layout for this page.
 */
export function useLayout(layout: Layout): void {
	state.setViewLayout(layout)
}
