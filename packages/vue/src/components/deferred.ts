import { defineComponent } from 'vue'
import { getByPath, wrap } from '@hybridly/utils'
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
		return wrap(this.$props.data).every((key) => getByPath(state.properties.value as any, key) !== undefined)
			? this.$slots.default?.()
			: this.$slots.fallback?.()
	},
})
