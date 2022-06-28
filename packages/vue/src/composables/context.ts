import { computed } from 'vue'
import { state } from '../stores/state'

/** Accesses the monolikit context. */
export function useContext() {
	return computed(() => state.context.value)
}
