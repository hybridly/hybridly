import type { RouterContext } from '@hybridly/core'
import { debug } from '@hybridly/utils'
import { ComponentOptions, Ref, ref, shallowRef, triggerRef, unref } from 'vue'
import { Layout } from '../composables/layout'
import { RouteCollection } from '../routes'

type MaybeRef<T> = Ref<T> | T

export const state = {
	context: ref<RouterContext>(),
	view: shallowRef<ComponentOptions>(),
	viewLayout: shallowRef<Layout>(),
	viewKey: ref<number>(),
	dialog: shallowRef<ComponentOptions>(),
	dialogKey: ref<number>(),
	routes: ref<RouteCollection>(),

	setRoutes(routes?: MaybeRef<RouteCollection>) {
		debug.adapter('vue:state:routes', 'Setting routes:', routes)
		if (routes) {
			state.routes.value = unref(routes)
		}
	},

	setView(view: MaybeRef<ComponentOptions>) {
		debug.adapter('vue:state:view', 'Setting view:', view)
		state.view.value = view
	},

	setViewLayout(layout: Layout) {
		debug.adapter('vue:state:view', 'Setting layout:', layout)
		state.viewLayout.value = layout
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
