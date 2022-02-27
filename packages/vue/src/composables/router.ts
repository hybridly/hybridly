import { resolveRouter, RouterContext } from '@sleightful/core'
import { state } from '../stores/state'

export function useRouter(context?: RouterContext) {
	return resolveRouter(() => context ?? state.context.value!)
}
