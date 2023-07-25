import type { MaybeRefOrGetter } from 'vue'
import { readonly, ref, toValue } from 'vue'
import type { RouteName, RouteParameters } from 'hybridly'
import { router } from 'hybridly'
import { registerHook } from './register-hook'

export function useRoute() {
	const current = ref(router.current())

	function matches<T extends RouteName>(name: MaybeRefOrGetter<T>, parameters?: RouteParameters<T>) {
		return router.matches(toValue(name), parameters)
	}

	registerHook('navigated', () => {
		current.value = router.current()
	})

	return {
		current: readonly(current),
		matches,
	}
}
