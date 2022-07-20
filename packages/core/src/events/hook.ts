/** Credit: https://github.com/vueuse/vueuse/blob/main/packages/shared/createEventHook/index.ts */
import { MaybePromise } from '../types'

export type CancellableEventFunction<T> = (param: T) => MaybePromise<boolean | void>
export type EventFunction<T> = (param: T) => MaybePromise<any>
export type EventHookOn<T> = (fn: EventFunction<T>) => { off: () => void }
export type EventHookOnce<T> = (fn: EventFunction<T>) => void
export type EventHookOff<T> = (fn: EventFunction<T>) => void
export type EventHookTrigger<T> = (param: T, additional?: EventFunction<T>) => Promise<boolean>

export interface EventHook<T> {
	on: EventHookOn<T>
	once: EventHookOnce<T>
	off: EventHookOff<T>
}

export interface InternalEventHook<T> {
	on: EventHookOn<T>
	once: EventHookOnce<T>
	off: EventHookOff<T>
	trigger: EventHookTrigger<T>
}

export function createEventHook<T>(): InternalEventHook<T> {
	const fns: Array<EventFunction<T>> = []

	const off = (fn: EventFunction<T>) => {
		const index = fns.indexOf(fn)
		if (index !== -1) {
			fns.splice(index, 1)
		}
	}

	const once = (fn: EventFunction<T>) => {
		fns.push((param: T) => {
			fn(param)
			off(fn)
		})
	}

	const on = (fn: EventFunction<T>) => {
		fns.push(fn)

		return {
			off: () => off(fn),
		}
	}

	const trigger = async(param: T, additionnal?: EventFunction<T>): Promise<boolean> => {
		let fnReturnedFalse = false

		if ((await additionnal?.(param)) === false) {
			fnReturnedFalse = true
		}

		for (const fn of fns) {
			if ((await fn(param)) === false) {
				fnReturnedFalse = true
			}
		}

		return !fnReturnedFalse
	}

	return {
		trigger,
		on,
		off,
		once,
	}
}
