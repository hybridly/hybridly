/* eslint-disable vue/order-in-components */
import { debug } from '@hybridly/utils'
import { toRaw, defineComponent, h } from 'vue'
import { dialogStore } from '../stores/dialog'
import { state } from '../stores/state'

export const wrapper = defineComponent({
	name: 'Hybridly',
	setup() {
		function renderLayout(view: any) {
			debug.adapter('vue:render:layout', 'Rendering layout.')

			if (typeof state.view.value?.layout === 'function') {
				return state.view.value.layout(h, view)
			}

			if (Array.isArray(state.view.value?.layout)) {
				return state.view
					.value!.layout.concat(view)
					.reverse()
					.reduce((child, layout) => {
						layout.inheritAttrs = !!layout.inheritAttrs

						return [
							h(layout, {
								...(state.view.value?.layoutProperties ?? {}),
								...state.properties.value,
							}, () => child),
							renderDialog(),
						]
					})
			}

			return [
				h(state.view.value?.layout, {
					...(state.view.value?.layoutProperties ?? {}),
					...state.properties.value,
				}, () => view),
				renderDialog(),
			]
		}

		function renderView() {
			debug.adapter('vue:render:view', 'Rendering view.')
			state.view.value!.inheritAttrs = !!state.view.value!.inheritAttrs

			return h(state.view.value!, {
				...state.properties.value,
				key: state.viewKey.value,
			})
		}

		function renderDialog() {
			if (dialogStore.state.component.value && dialogStore.state.properties.value) {
				debug.adapter('vue:render:dialog', 'Rendering dialog.')

				return h(dialogStore.state.component.value!, {
					...dialogStore.state.properties.value,
					key: dialogStore.state.key.value,
				})
			}
		}

		return (...a) => {
			if (!state.view.value) {
				return
			}

			debug.adapter('vue:render:wrapper', 'Rendering wrapper component.', a.map(toRaw))

			const view = renderView()

			if (state.viewLayout.value) {
				state.view.value.layout = state.viewLayout.value
				// Maybe we need that back, but it's causing a fast re-render that blocks animations
				// state.viewLayout.value = undefined
			}

			if (state.viewLayoutProperties.value) {
				state.view.value.layoutProperties = state.viewLayoutProperties.value
				state.viewLayoutProperties.value = undefined
			}

			if (state.view.value.layout) {
				return renderLayout(view)
			}

			return [view, renderDialog()]
		}
	},
})
