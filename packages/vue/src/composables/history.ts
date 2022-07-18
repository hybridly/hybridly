import { ref, toRaw, watch } from 'vue'
import { router } from '@hybridly/core'

/**
 * Returns a ref with a value saved in the history state through Hybridly.
 * The state is linked to a specific browser history entry.
 */
export function useHistoryState<T = any>(key: string, initial: T) {
	const value = ref<T>(router.history.get<T>(key) ?? initial)

	watch(value, (value) => {
		router.history.remember(key, toRaw(value))
	}, { immediate: true, deep: true })

	return value
}
