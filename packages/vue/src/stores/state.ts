import type { RouterContext } from '@hybridly/core'
import { debug } from '@hybridly/utils'
import { ComponentOptions, Ref, ref, shallowRef, triggerRef, unref } from 'vue'

type MaybeRef<T> = Ref<T> | T

export const state = {
	context: ref<RouterContext>(),
	view: shallowRef<ComponentOptions>(),
	viewKey: ref<number>(),
	dialog: shallowRef<ComponentOptions>(),
	dialogKey: ref<number>(),

	setView(view: MaybeRef<ComponentOptions>) {
		debug.adapter('vue:state:view', 'Setting view:', view)
		state.view.value = view
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
