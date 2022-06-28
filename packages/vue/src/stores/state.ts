import { debug, RouterContext } from '@monolikit/core'
import { ComponentOptions, markRaw, Ref, ref, unref } from 'vue'

type MaybeRef<T> = Ref<T> | T

export const state = {
	context: ref<RouterContext>(),
	view: ref<ComponentOptions>(),
	viewKey: ref<number>(),
	dialog: ref<ComponentOptions>(),
	dialogKey: ref<number>(),

	setView(view: MaybeRef<ComponentOptions>) {
		debug.adapter('vue:state:view', 'Setting view:', view)
		this.view.value = markRaw(unref(view))
	},

	setDialog(dialog: MaybeRef<ComponentOptions>) {
		debug.adapter('vue:state:dialog', 'Setting dialog:', dialog)
		this.dialog.value = markRaw(unref(dialog))
	},

	setContext(context: MaybeRef<RouterContext>) {
		debug.adapter('vue:state:context', 'Setting context:', context)
		this.context.value = unref(context)
	},

	setViewKey(key: MaybeRef<number>) {
		debug.adapter('vue:state:key', 'Setting view key:', key)
		this.viewKey.value = unref(key)
	},

	setDialogKey(key: MaybeRef<number>) {
		debug.adapter('vue:state:key', 'Setting dialog key:', key)
		this.dialogKey.value = unref(key)
	},
}
