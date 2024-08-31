import { defineComponent } from 'vue'
import { getByPath } from '@hybridly/utils'
import { state } from '../stores/state'

export const Deferred = defineComponent({
	name: 'Deferred',
	props: {
		data: {
			type: [String, Array<string>],
			required: true,
		},
	},
	render() {
		const keys = (Array.isArray(this.$props.data) ? this.$props.data : [this.$props.data])
		return keys.every((key) => getByPath(state.properties.value as any, key) !== undefined)
			? this.$slots.default?.()
			: this.$slots.fallback?.()
	},
})
