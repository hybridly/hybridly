import { test, vi } from 'vitest'
import { defineComponent } from 'vue'
import { applyDefaultLayout, resolveDefaultLayout } from '../src/default-layout'

test('it resolves a default layout component', ({ expect }) => {
	const layout = defineComponent({ name: 'DefaultLayout' })

	expect(resolveDefaultLayout(layout)).toBe(layout)
})

test('it resolves a default layout through a callback', ({ expect }) => {
	const layout = defineComponent({ name: 'DefaultLayout' })
	const resolver = vi.fn(() => layout)

	expect(resolveDefaultLayout(resolver)).toBe(layout)
	expect(resolver).toHaveBeenCalledOnce()
})

test('it resolves an array of default layouts', ({ expect }) => {
	const first = defineComponent({ name: 'FirstLayout' })
	const second = defineComponent({ name: 'SecondLayout' })

	expect(resolveDefaultLayout([first, second])).toEqual([first, second])
})

test('it applies a default layout when missing', ({ expect }) => {
	const view = defineComponent({ name: 'View' })
	const layout = defineComponent({ name: 'DefaultLayout' })

	applyDefaultLayout(view, layout)

	expect(view.layout).toBe(layout)
})

test('it does not override a view layout', ({ expect }) => {
	const existingLayout = defineComponent({ name: 'ExistingLayout' })
	const view = defineComponent({ ame: 'View', layout: existingLayout })
	const defaultLayout = defineComponent({ name: 'DefaultLayout' })

	applyDefaultLayout(view, defaultLayout)

	expect(view.layout).toBe(existingLayout)
})
