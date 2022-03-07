import { resolveRouter, RouterContext } from '@sleightful/core'
import { toRaw } from 'vue'
import { state } from '../stores/state'

export function useRouter(context?: RouterContext) {
	return resolveRouter(() => toRaw(context ?? state.context.value!))
}
