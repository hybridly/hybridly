import { type HybridRequestOptions, router } from '@hybridly/core'
import { wrap } from '@hybridly/utils'
import type { PropType, SlotsType } from 'vue'
import { defineComponent, h, onMounted, onUnmounted, ref, watch } from 'vue'
import { LoadStateSlotProps, useLoadState } from './load-state'

export const WhenVisible = defineComponent({
	name: 'WhenVisible',
	emits: {
		loading: (_state: LoadStateSlotProps) => true,
	},
	props: {
		as: {
			type: String,
			default: 'div',
		},
		data: {
			type: [String, Array] as PropType<string | string[]>,
			required: true,
		},
		buffer: {
			type: Number,
			default: 0,
		},
		root: {
			type: [String, Object] as PropType<string | Element | null>,
			default: null,
		},
		once: {
			type: Boolean,
			default: true,
		},
		fallbackOnHidden: {
			type: Boolean,
			default: false,
		},
		options: {
			type: Object as PropType<Omit<HybridRequestOptions, 'url' | 'data' | 'method'>>,
			default: () => ({}),
		},
	},
	slots: Object as SlotsType<{
		default: LoadStateSlotProps
		fallback: LoadStateSlotProps
	}>,
	setup(properties, { emit, slots }) {
		const isVisible = ref(false)
		const element = ref(null)
		const hasRan = ref(false)
		let observer: IntersectionObserver | undefined

		const { getSlotProps } = useLoadState(() => wrap(properties.data) as string[])

		function registerObserver() {
			observer?.disconnect()

			const root = typeof properties.root === 'string'
				? document.querySelector(properties.root)
				: properties.root

			observer = new IntersectionObserver(
				([entry]) => isVisible.value = entry.isIntersecting,
				{
					root,
					rootMargin: `${properties.buffer}px 0px ${properties.buffer}px 0px`,
				},
			)

			if (element.value) {
				observer.observe(element.value)
			}
		}

		onMounted(() => {
			registerObserver()

			watch(() => [properties.buffer, properties.root], () => {
				registerObserver()
			})
		})

		onUnmounted(() => {
			observer?.disconnect()
		})

		watch(isVisible, (visible) => {
			if (hasRan.value && properties.once) {
				return
			}

			if (visible) {
				emit('loading', getSlotProps())

				router.reload({
					...properties.options,
					only: properties.data,
				})

				hasRan.value = true
			}
		})

		return () => {
			const slotProperties = getSlotProps()
			const shouldRender = slotProperties.loaded && (isVisible.value || !properties.fallbackOnHidden)

			return h(properties.as, { ref: element }, [
				shouldRender
					? slots.default?.(slotProperties)
					: slots.fallback?.(slotProperties),
			])
		}
	},
})
