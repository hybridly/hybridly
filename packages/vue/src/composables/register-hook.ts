import { registerHook as coreRegisterHook } from '@hybridly/core'
import { getCurrentInstance, onUnmounted } from 'vue'

/**
 * Registers a global hook.
 * If called inside a component, unregisters after the component is unmounted.
 */
export const registerHook: typeof coreRegisterHook = (hook, fn, options) => {
	const unregister = coreRegisterHook(hook, fn, options)

	if (getCurrentInstance()) {
		onUnmounted(() => unregister())
	}

	return unregister
}
