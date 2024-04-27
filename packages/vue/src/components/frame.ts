/* eslint-disable vue/order-in-components */
import type { Frame } from '@hybridly/core'
import { getRouterContext, isHybridResponse, makeUrl, performHybridRequest } from '@hybridly/core'
import { random } from '@hybridly/utils'
import type { Component } from 'vue'
import { defineComponent, h, ref, shallowRef, watch } from 'vue'

export const HybridFrame = defineComponent({
	name: 'HybridFrame',
	setup(props, { slots }) {
		const frame = shallowRef<Component | undefined>()
		const key = ref<string | undefined>()
		const properties = ref<Record<string, any>>({})

		watch([() => props.href, () => props.component], async() => {
			if (!props.href) {
				frame.value = undefined
				properties.value = {}
				return
			}

			const context = getRouterContext()
			const response = await performHybridRequest(makeUrl(props.href!), {})

			if (!isHybridResponse(response)) {
				console.error('Frames must receive a hybrid response.')
				return
			}

			const payload = response.data as Frame
			const component = payload?.component ?? props.component

			frame.value = component
				? await context.adapter.resolveComponent(component)
				: undefined

			properties.value = payload.properties
			key.value = random()
		}, { immediate: true })

		return () => {
			if (!props.href) {
				return
			}

			if (!frame.value && !slots?.default) {
				console.warn('Frames must define a slot or a component.')
				return
			}

			if (slots?.default) {
				return slots.default({
					...properties.value,
					key: key.value,
				})
			}

			return h(frame.value!, {
				...properties.value,
				key: key.value,
			})
		}
	},
	props: {
		href: {
			type: String,
			required: false,
			default: undefined,
		},
		component: {
			type: String,
			required: false,
			default: undefined,
		},
	},
})
