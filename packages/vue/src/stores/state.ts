import { createRouter } from 'sleightful'
import { ComponentOptions, markRaw, Ref, ref, unref } from 'vue'
import type { AsyncReturnType } from 'type-fest'

type MaybeRef<T> = Ref<T> | T
export type Router = Omit<AsyncReturnType<typeof createRouter>, 'adapter'>

export const state = {
	router: ref<Router>(),
	component: ref<ComponentOptions>(),
	key: ref<number>(),

	setComponent(component: MaybeRef<ComponentOptions>) {
		this.component.value = markRaw(unref(component))
	},

	setRouter(router: MaybeRef<Router>) {
		this.router.value = unref(router)
	},

	setKey(key: MaybeRef<number>) {
		this.key.value = unref(key)
	},
}
