import { debug, RouterContext } from '@sleightful/core'
import { ComponentOptions, defineComponent, h, PropType } from 'vue'
import { state } from '../stores/state'

export const wrapper = defineComponent({
	name: 'Sleightful',
	setup({ context, component }) {
		if (typeof window !== 'undefined') {
			state.setContext(context)
			state.setView(component)

			if (!context || !component) {
				throw new Error('Sleightful was not properly initialized. The context or initial component is missing.')
			}
		}

		function renderLayout(child: any) {
			debug.adapter('vue:render:layout', 'Rendering layout.')

			if (typeof state.view.value!.layout === 'function') {
				return state.view.value!.layout(h, child)
			}

			if (Array.isArray(state.view.value!.layout)) {
				return state.view.value!.layout
					.concat(child)
					.reverse()
					.reduce((child, layout) => {
						layout.inheritAttrs = !!layout.inheritAttrs
						return h(layout, { ...state.context.value!.view.properties }, () => child)
					})
			}

			return [
				h(state.view.value!.value.layout, { ...state.context.value!.view.properties }, () => child),
				renderDialog(),
			]
		}

		function renderView() {
			debug.adapter('vue:render:view', 'Rendering view.')
			state.view.value!.inheritAttrs = !!state.view.value!.inheritAttrs

			return h(state.view.value!, {
				...state.context.value!.view.properties,
				dialog: false,
				key: state.viewKey.value,
			})
		}

		function renderDialog() {
			debug.adapter('vue:render:dialog', 'Rendering dialog.')

			if (state.dialog.value) {
				return h(state.dialog.value!, {
					...state.dialog.value.properties,
					key: state.dialogKey.value,
				})
			}
		}

		return () => {
			if (state.view.value) {
				const view = renderView()

				if (state.view.value.layout) {
					return renderLayout(view)
				}

				return [view, renderDialog()]
			}
		}
	},
	props: {
		context: {
			type: Object as PropType<RouterContext>,
			required: true,
		},
		component: {
			type: Object as PropType<ComponentOptions>,
			required: true,
		},
	},
})
