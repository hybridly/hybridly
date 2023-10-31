import { registerHook } from 'hybridly'
import { reactive } from 'vue'

/**
 * Access reactive query parameters.
 *
 * @see https://hybridly.dev/api/utils/use-query-parameters.html
 */
export function useQueryParameters<T extends Record<string, any> = Record<string, any>>() {
	const state: Record<string, any> = reactive({})

	function updateState() {
		const params = new URLSearchParams(window.location.search)
		const unusedKeys = new Set(Object.keys(state))
		for (const key of params.keys()) {
			const paramsForKey = params.getAll(key)
			state[key] = paramsForKey.length > 1
				? paramsForKey
				: (params.get(key) || '')
			unusedKeys.delete(key)
		}
		Array.from(unusedKeys).forEach((key) => delete state[key])
	}

	updateState()
	registerHook('after', updateState)

	return state as T
}
