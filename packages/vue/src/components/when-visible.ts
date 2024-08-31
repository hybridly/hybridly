import { computed, defineComponent, h, onMounted, onUnmounted, ref } from 'vue'
import { getByPath } from '@hybridly/utils'
import { state } from '../stores/state'

export const WhenVisible = defineComponent({
	name: 'WhenVisible',
	props: {
		as: {
			type: String,
			default: 'div',
		},
		data: {
			type: [String, Array<string>],
			required: true,
		},
		buffer: {
			type: Number,
			default: 0,
		},
	},
	setup(props, { slots }) {
		const isVisible = ref(false)
		const rootEl = ref(null)
		let observer: IntersectionObserver | undefined

		const shouldRender = computed(() => {
			const keys = Array.isArray(props.data) ? props.data : [props.data]
			return keys.every((key) => getByPath(state.properties.value as any, key) !== undefined)
		})

		onMounted(() => {
			observer = new IntersectionObserver(
				([entry]) => {
					isVisible.value = entry.isIntersecting
				},
				{ rootMargin: `${props.buffer}px 0px ${props.buffer}px 0px` },
			)

			if (rootEl.value) {
				observer.observe(rootEl.value)
			}
		})

		onUnmounted(() => {
			if (observer) {
				observer.disconnect()
			}
		})

		return () => h(props.as, { ref: rootEl }, [
			isVisible.value && shouldRender.value
				? slots.default?.()
				: slots.fallback?.(),
		])
	},
})
