import { RouterContext } from '@sleightful/core'
import { ComponentOptions, defineComponent, h, PropType } from 'vue'
import { state } from '../stores/state'

export const wrapper = defineComponent({
	name: 'Sleightful',
	setup({ context, component }) {
		if (typeof window !== 'undefined') {
			state.setContext(context)
			state.setComponent(component)

			if (!context || !component) {
				throw new Error('Sleightful was not properly initialized. The context or initial component is missing.')
			}
		}

		return () => {
			if (state.component.value) {
				state.component.value.inheritAttrs = !!state.component.value.inheritAttrs

				const child = h(state.component.value, {
					...state.context.value?.view?.properties,
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

							return h(layout, { ...state.context.value?.view?.properties }, () => child)
						})
				}

				return child
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
