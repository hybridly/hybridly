import { ComponentOptions, defineComponent, h, PropType } from 'vue'
import { Router, state } from '../stores/state'

export const wrapper = defineComponent({
	name: 'Sleightful',
	setup({ router, component }) {
		if (typeof window !== 'undefined') {
			state.setRouter(router)
			state.setComponent(component)

			if (!router || !component) {
				throw new Error('Sleightful was not properly initialized. The router or initial component is missing.')
			}
		}

		return () => {
			if (state.component.value) {
				state.component.value.inheritAttrs = !!state.component.value.inheritAttrs

				const child = h(state.component.value, {
					...state.router.value?.context.view?.properties,
					key: state.key.value,
				})

				if (state.component.value.layout) {
					if (typeof state.component.value.layout === 'function') {
						return state.component.value.layout(h, child)
					}

					return (Array.isArray(state.component.value.layout) ? state.component.value.layout : [state.component.value.layout])
						.concat(child)
						.reverse()
						.reduce((child: any, layout: any) => {
							layout.inheritAttrs = !!layout.inheritAttrs

							return h(layout, { ...state.router.value?.context.view?.properties }, () => child)
						})
				}

				return child
			}
		}
	},
	props: {
		router: {
			type: Object as PropType<Router>,
			required: true,
		},
		component: {
			type: Object as PropType<ComponentOptions>,
			required: true,
		},
	},
})
