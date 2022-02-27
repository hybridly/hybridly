import { RouterContext } from '@sleightful/core'
import { ComponentOptions, markRaw, Ref, ref, unref } from 'vue'

type MaybeRef<T> = Ref<T> | T

export const state = {
	context: ref<RouterContext>(),
	component: ref<ComponentOptions>(),
	key: ref<number>(),

	setComponent(component: MaybeRef<ComponentOptions>) {
		this.component.value = markRaw(unref(component))
	},

	setContext(context: MaybeRef<RouterContext>) {
		this.context.value = unref(context)
	},

	setKey(key: MaybeRef<number>) {
		this.key.value = unref(key)
	},
}
