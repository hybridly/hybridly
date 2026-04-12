import { registerHook } from '@hybridly/core'
import { wrap } from '@hybridly/utils'
import { get } from 'es-toolkit/compat'
import { onMounted, onUnmounted, ref } from 'vue'
import { state } from '../stores/state'

export interface LoadStateSlotProps {
	reloading: boolean
	loading: boolean
	loaded: boolean
}

function keysAreBeingReloaded(only: undefined | string | string[], except: undefined | string | string[], keys: string[]): boolean {
	only = wrap(only)
	except = wrap(except)

	if (only.length > 0) {
		return keys.some((key) => only.includes(key))
	}

	if (except.length > 0) {
		return !keys.some((key) => except.includes(key))
	}

	return true
}

export function useLoadState(getKeys: () => string[]) {
	const hasLoadedOnce = ref(getKeys().every((key) => get(state.properties.value, key) !== undefined))
	const reloading = ref(false)
	const activeReloads = new Set<string>()

	let removeStartListener: (() => void) | null = null
	let removeFinishListener: (() => void) | null = null

	onMounted(() => {
		removeStartListener = registerHook('start', (request) => {
			if (hasLoadedOnce.value === false) {
				return
			}

			if (request.options.preserveState !== true) {
				return
			}

			const keys = getKeys()

			if (!keysAreBeingReloaded(request.options.only, request.options.except, keys)) {
				return
			}

			activeReloads.add(request.id)
			reloading.value = true
		})

		removeFinishListener = registerHook('after', (request) => {
			if (activeReloads.has(request.id)) {
				activeReloads.delete(request.id)
				reloading.value = activeReloads.size > 0
			}
		})
	})

	onUnmounted(() => {
		removeStartListener?.()
		removeFinishListener?.()
		activeReloads.clear()
	})

	function getSlotProps(): LoadStateSlotProps {
		const loaded = getKeys().every((key) => get(state.properties.value, key) !== undefined)

		if (loaded) {
			hasLoadedOnce.value = true
		}

		return {
			reloading: reloading.value,
			loading: !loaded || reloading.value,
			loaded,
		}
	}

	return {
		getSlotProps,
	}
}
