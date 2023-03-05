import type { ComputedRef } from 'vue'
import { computed, readonly } from 'vue'
import { state } from '../stores/state'
import { toReactive } from '../utils'

/** Accesses all current properties. */
export function useProperties<T extends object, Global extends GlobalHybridlyProperties>() {
	return readonly(toReactive(computed(() => state.context.value?.view.properties as T & Global)))
}

/**
 * Accesses a property with the given type.
 * @experimental Workaround for not being able to type `useProperty`, might be removed in the future.
 */
export function useTypedProperty<T>(path: string, fallback?: T): ComputedRef<T> {
	return computed(() => (path as string)
		.split('.')
		.reduce((o: any, i: string) => o[i], state.context.value?.view.properties) as any
		?? fallback,
	) as any
}

/** Accesses a property with a dot notation. */
export function useProperty<
	T = GlobalHybridlyProperties,
	P extends Path<T> = Path<T>,
	Fallback extends PathValue<T, P> = PathValue<T, P>
>(
	path: [P] extends [never] ? string : P,
	fallback?: Fallback,
): ComputedRef<[PathValue<T, P>] extends [never] ? Fallback : PathValue<T, P>> {
	return computed(() => (path as string)
		.split('.')
		.reduce((o: any, i: string) => o[i], state.context.value?.view.properties) as any
		?? fallback,
	)
}

// Credit to @diegohaz for the dot-notation access implementation
// https://twitter.com/diegohaz/status/1309489079378219009

type PathImpl<T, K extends keyof T> =
	K extends string
		? NonNullable<T[K]> extends Record<string, any>
			? NonNullable<T[K]> extends ArrayLike<any>
				? K | `${K}.${PathImpl<NonNullable<T[K]>, Exclude<keyof NonNullable<T[K]>, keyof any[]>>}`
				: K | `${K}.${PathImpl<NonNullable<T[K]>, keyof NonNullable<T[K]>>}`
			: K
		: never

type Path<T> = PathImpl<T, keyof T> | keyof T

type PathValue<T, P extends Path<T>> =
	P extends `${infer K}.${infer Rest}`
		? K extends keyof T
			? Rest extends Path<T[K]>
				? PathValue<T[K], Rest>
				: never
			: never
		: P extends keyof T
			? T[P]
			: never
