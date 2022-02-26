import { computed } from 'vue'
import { state } from '../stores/state'

/** Accesses the sleightful context. */
export function useContext() {
	return computed(() => state.router.value?.context)
}
