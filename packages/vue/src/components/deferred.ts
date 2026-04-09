import { registerHook } from '@hybridly/core'
import { wrap } from '@hybridly/utils'
import { get } from 'es-toolkit/compat'
import { defineComponent, onMounted, onUnmounted, PropType, ref, SlotsType } from 'vue'
import { state } from '../stores/state'

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

export const Deferred = defineComponent({
	name: 'Deferred',
	props: {
		data: {
			type: [String, Array] as PropType<string | string[]>,
			required: true,
		},
	},
	slots: Object as SlotsType<{
		default: {
			reloading: boolean
			loading: boolean
			loaded: boolean
		}
		fallback: {}
	}>,
	setup(props, { slots }) {
		const hasLoadedOnce = ref(wrap(props.data).every((key) => get(state.properties.value, key) !== undefined))
		const reloading = ref(false)
		const activeReloads = new Set<string>()

		let removeStartListener: (() => void) | null = null
		let removeFinishListener: (() => void) | null = null

		onMounted(() => {
			const keys = (Array.isArray(props.data) ? props.data : [props.data]) as string[]

			removeStartListener = registerHook('start', (request) => {
				// if we haven't loaded at least once, `reloading`
				// must stay `false` because `loading` will be true
				if (hasLoadedOnce.value === false) {
					return
				}

				if (request.options.preserveState !== true) {
					return
				}

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

		return () => {
			const hasRequiredProperties = wrap(props.data).every((key) => get(state.properties.value, key) !== undefined)
			const currentlyLoading = !hasRequiredProperties || reloading.value

			if (hasRequiredProperties) {
				hasLoadedOnce.value = true
			}

			if (!hasRequiredProperties && !!slots.fallback) {
				return slots.fallback({})
			}

			return slots.default?.({
				reloading: reloading.value,
				loading: currentlyLoading,
				loaded: hasRequiredProperties,
			})
		}
	},
})
