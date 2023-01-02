import type { RouterContext } from '@hybridly/core'
import { debug } from '@hybridly/utils'
import type { ComponentOptions, Ref } from 'vue'
import { ref, shallowRef, triggerRef, unref } from 'vue'
import type { Layout } from '../composables/layout'

type MaybeRef<T> = Ref<T> | T

export const state = {
	context: ref<RouterContext>(),
	view: shallowRef<ComponentOptions>(),
	properties: ref<any>(),
	viewLayout: shallowRef<Layout>(),
	viewLayoutProperties: ref<any>(),
	viewKey: ref<number>(),
	dialog: shallowRef<ComponentOptions>(),
	dialogKey: ref<number>(),

	setView(view: MaybeRef<ComponentOptions>) {
		debug.adapter('vue:state:view', 'Setting view:', view)
		state.view.value = view
	},

	setProperties(properties: any) {
		debug.adapter('vue:state:view', 'Setting properties:', properties)
		state.properties.value = properties
	},

	setViewLayout(layout: Layout | Layout[]) {
		debug.adapter('vue:state:view', 'Setting layout', layout)
		state.viewLayout.value = layout
	},

	setViewLayoutProperties(properties: any) {
		debug.adapter('vue:state:view', 'Setting layout properties:', properties)
		state.viewLayoutProperties.value = properties
	},

	setDialog(dialog: MaybeRef<ComponentOptions>) {
		debug.adapter('vue:state:dialog', 'Setting dialog:', dialog)
		state.dialog.value = dialog
	},

	setContext(context: MaybeRef<RouterContext>) {
		debug.adapter('vue:state:context', 'Setting context:', context)
		state.context.value = unref(context)
		triggerRef(state.context)
	},

	setViewKey(key: MaybeRef<number>) {
		debug.adapter('vue:state:key', 'Setting view key:', key)
		state.viewKey.value = unref(key)
	},

	setDialogKey(key: MaybeRef<number>) {
		debug.adapter('vue:state:key', 'Setting dialog key:', key)
		state.dialogKey.value = unref(key)
	},
}
