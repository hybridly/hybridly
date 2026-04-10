import type { Errors, Validation } from '@hybridly/core'
import { computed, readonly } from 'vue'
import { state } from '../stores/state'
import { toReactive } from '../utils'

/** Accesses all validation errors grouped by bag name. */
export function useValidation<T extends Validation = Validation>() {
	return readonly(toReactive(computed(() => state.context.value?.validation as T ?? {} as T)))
}

/** Accesses validation errors for a single bag. */
export function useValidationBag<T extends Errors = Errors>(bag: string = 'default') {
	return readonly(toReactive(computed(() => state.context.value?.validation?.[bag] as T ?? {} as T)))
}
