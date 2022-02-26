import { ComponentOptions, defineComponent, h } from 'vue'
import { Router, state } from '../stores/state'

export interface WrapperOptions {
	router: Router
	component: ComponentOptions
}

export const wrapper = defineComponent<WrapperOptions>({
	name: 'Inertia',
	async setup({ router, component }) {
		if (typeof window !== 'undefined') {
			state.setRouter(router)
			state.setComponent(component)
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
})
