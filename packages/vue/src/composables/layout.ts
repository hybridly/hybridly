import type { ComponentOptions } from 'vue'
import { state } from '../stores/state'

export type Layout = ComponentOptions | (() => ComponentOptions) | [ComponentOptions]

/**
 * Sets the persistent layout for this page.
 */
export function defineLayout(layout: Layout): void {
	state.setViewLayout(layout)
}
