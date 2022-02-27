import type { Router } from './stores/state'
import { state } from './stores/state'

// TODO: transform to proxy

export const router: Omit<Router, 'context'> = {
	abort: async() => await state.router.value!.abort(),
	visit: async(...args) => await state.router.value!.visit(...args),
	get: async(...args) => await state.router.value!.get(...args),
	post: async(...args) => await state.router.value!.post(...args),
	put: async(...args) => await state.router.value!.put(...args),
	patch: async(...args) => await state.router.value!.patch(...args),
	reload: async(...args) => await state.router.value!.reload(...args),
	delete: async(...args) => await state.router.value!.delete(...args),
}
