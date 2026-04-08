import type { PropType } from 'vue'
import { computed, defineComponent, h, onMounted, onUnmounted, ref, watch } from 'vue'
import { getByPath, wrap } from '@hybridly/utils'
import { type HybridRequestOptions, router } from '@hybridly/core'
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
		once: {
			type: Boolean,
			default: false,
		},
		options: {
			type: Object as PropType<Omit<HybridRequestOptions, 'url' | 'data' | 'method'>>,
			default: () => ({}),
		},
	},
	setup(props, { slots }) {
		const isVisible = ref(false)
		const rootEl = ref(null)
		const hasRan = ref(false)
		let observer: IntersectionObserver | undefined

		const shouldRender = computed(() => {
			return wrap(props.data).every((key) => getByPath(state.properties.value as any, key) !== undefined)
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

		watch(isVisible, () => {
			if (hasRan.value && props.once) {
				return
			}

			if (isVisible.value) {
				router.reload({
					...props.options,
					only: props.data,
				})

				hasRan.value = true
			}
		})

		return () => h(props.as, { ref: rootEl }, [
			isVisible.value && shouldRender.value
				? slots.default?.()
				: slots.fallback?.(),
		])
	},
})
