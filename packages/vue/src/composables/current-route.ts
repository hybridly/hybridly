import type { MaybeRefOrGetter } from 'vue'
import { readonly, ref, toValue } from 'vue'
import type { RouteName, RouteParameters } from '@hybridly/core'
import { router } from '@hybridly/core'
import { registerHook } from './register-hook'

const isNavigating = ref(false)

export function useRoute() {
	const current = ref(router.current())

	function matches<T extends RouteName>(name: MaybeRefOrGetter<T>, parameters?: RouteParameters<T>) {
		return router.matches(toValue(name), parameters)
	}

	registerHook('before', () => isNavigating.value = true)
	registerHook('after', () => isNavigating.value = false)
	registerHook('navigated', () => {
		current.value = router.current()
	})

	return {
		isNavigating: readonly(isNavigating),
		current: readonly(current),
		matches,
	}
}
