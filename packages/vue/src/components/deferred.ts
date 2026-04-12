import { wrap } from '@hybridly/utils'
import { defineComponent, PropType, SlotsType } from 'vue'
import { LoadStateSlotProps, useLoadState } from './load-state'

export const Deferred = defineComponent({
	name: 'Deferred',
	props: {
		data: {
			type: [String, Array] as PropType<string | string[]>,
			required: true,
		},
	},
	slots: Object as SlotsType<{
		default: LoadStateSlotProps
		fallback: LoadStateSlotProps
	}>,
	setup(props, { slots }) {
		const { getSlotProps } = useLoadState(() => wrap(props.data) as string[])

		return () => {
			const slotProps = getSlotProps()

			if (!slotProps.loaded && !!slots.fallback) {
				return slots.fallback(slotProps)
			}

			return slots.default?.(slotProps)
		}
	},
})
