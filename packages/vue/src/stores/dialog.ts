import { ref, shallowRef, unref } from 'vue'
import type { ComponentOptions } from 'vue'
import { debug } from '@hybridly/utils'
import type { MaybeRef } from '../utils'

const DEBUG_KEY = 'vue:state:dialog'

export const dialogStore = {
	state: {
		component: shallowRef<ComponentOptions>(),
		properties: ref<any>(),
		key: ref<string>(),
		show: ref<boolean>(),
	},

	removeComponent() {
		if (dialogStore.state.component.value) {
			debug.adapter(DEBUG_KEY, 'Removing dialog.')
			dialogStore.state.component.value = undefined
		}
	},

	setComponent(component?: MaybeRef<ComponentOptions>) {
		debug.adapter(DEBUG_KEY, 'Setting dialog component:', component)
		dialogStore.state.component.value = component
	},

	setProperties(properties?: MaybeRef<any>) {
		debug.adapter(DEBUG_KEY, 'Setting dialog properties:', properties)
		dialogStore.state.properties.value = unref(properties)
	},

	setKey(key: MaybeRef<string>) {
		debug.adapter(DEBUG_KEY, 'Setting dialog key:', key)
		dialogStore.state.key.value = unref(key)
	},

	show() {
		if (!dialogStore.state.show.value) {
			debug.adapter(DEBUG_KEY, 'Showing the dialog.')
			dialogStore.state.show.value = true
		}
	},

	hide() {
		if (dialogStore.state.show.value) {
			debug.adapter(DEBUG_KEY, 'Hiding the dialog.')
			dialogStore.state.show.value = false
		}
	},
}
