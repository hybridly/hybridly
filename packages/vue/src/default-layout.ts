import type { Component } from 'vue'

type ViewLayout = Component | Component[]
type ComponentWithLayout = Component & { layout?: ViewLayout }

export type DefaultLayout = ViewLayout | (() => ViewLayout)

export function resolveDefaultLayout(layout: DefaultLayout | undefined): ViewLayout | undefined {
	if (!layout) {
		return undefined
	}

	if (Array.isArray(layout)) {
		return layout
	}

	if (typeof layout === 'function' && layout.length === 0) {
		return (layout as () => ViewLayout)()
	}

	return layout
}

export function applyDefaultLayout(component: Component, layout: ViewLayout | undefined): Component {
	if (!layout) {
		return component
	}

	const resolved = component as ComponentWithLayout

	if (resolved.layout === undefined) {
		resolved.layout = layout
	}

	return component
}
