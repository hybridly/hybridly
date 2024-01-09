import type { RouterContext } from '@hybridly/core'
import { debug } from '@hybridly/utils'
import type { ComponentOptions, Ref } from 'vue'
import { ref, shallowRef, triggerRef, unref } from 'vue'
import type { Layout } from '../composables/layout'

type MaybeRef<T> = Ref<T> | T

export const state = {
	context: shallowRef<RouterContext>(),
	view: shallowRef<ComponentOptions>(),
	properties: ref<any>(),
	viewLayout: undefined as any,
	viewLayoutProperties: undefined as any,
	lastLayoutUpdateView: undefined as any,
	viewKey: ref<string>(),

	setView(view: MaybeRef<ComponentOptions>) {
		debug.adapter('vue:state:view', 'Storing view:', view)
		state.view.value = view
	},

	setProperties(properties: any) {
		debug.adapter('vue:state:view', 'Storing properties:', properties)
		state.properties.value = properties
	},

	setViewLayout(layout: Layout | Layout[], properties?: any) {
		if (state.lastLayoutUpdateView === state.view.value?.__file) {
			state.lastLayoutUpdateView = undefined
			state.viewLayout = undefined
			state.viewLayoutProperties = undefined
			debug.adapter('vue:state:view', 'Skipping updating layout on the same view')
			return
		}

		debug.adapter('vue:state:view', 'Storing layout', layout)
		state.lastLayoutUpdateView = state.view.value?.__file
		state.viewLayoutProperties = properties ?? state.viewLayoutProperties
		state.viewLayout = layout
		triggerRef(state.view)
	},

	setViewLayoutProperties(properties: any) {
		if (state.lastLayoutUpdateView === state.view.value?.__file) {
			state.lastLayoutUpdateView = undefined
			state.viewLayoutProperties = undefined
			debug.adapter('vue:state:view', 'Skipping updating properties on the same view')
			return
		}

		debug.adapter('vue:state:view', 'Storing layout properties:', properties)
		state.lastLayoutUpdateView = state.view.value?.__file
		state.viewLayoutProperties = properties
		triggerRef(state.view)
	},

	setContext(context: MaybeRef<RouterContext>) {
		debug.adapter('vue:state:context', 'Storing context:', context)
		state.context.value = unref(context)
	},

	setViewKey(key: MaybeRef<string>) {
		debug.adapter('vue:state:key', 'Storing view key:', key)
		state.viewKey.value = unref(key)
	},
}
