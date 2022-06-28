import { computed } from 'vue'
import { state } from '../stores/state'

/** Accesses the hybridly context. */
export function useContext() {
	return computed(() => state.context.value)
}
