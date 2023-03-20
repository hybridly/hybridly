import type { ComputedRef } from 'vue'
import { computed, readonly } from 'vue'
import { state } from '../stores/state'
import { toReactive } from '../utils'

/** Accesses all current properties. */
export function useProperties<T extends object, Global extends GlobalHybridlyProperties>() {
	return readonly(toReactive(computed(() => state.context.value?.view.properties as T & Global)))
}

/** Accesses a property with a dot notation. */
export function useProperty<
	F = never,
	T = GlobalHybridlyProperties,
	P extends Path<T> = Path<T>
>(
	path: [F] extends [never]
		? ([P] extends [never] ? string : P)
		: string,
): ComputedRef<[F] extends [never] ? ([PathValue<T, P>] extends [never] ? never : PathValue<T, P>) : F> {
	return computed(() => {
		return (path as string)
			.split('.')
			.reduce((o: any, i: string) => o?.[i], state.context.value?.view.properties) as any
	})
}

// Credit to @diegohaz for the dot-notation access implementation
// https://twitter.com/diegohaz/status/1309489079378219009

type PathImpl<T, K extends keyof T> =
		K extends string
			? T[K] extends undefined
				? undefined
				: NonNullable<T[K]> extends Record<string, any>
					? NonNullable<T[K]> extends ArrayLike<any>
						? K | `${K}.${PathImpl<NonNullable<T[K]>, Exclude<keyof NonNullable<T[K]>, keyof any[]>>}`
						: K | `${K}.${PathImpl<NonNullable<T[K]>, keyof NonNullable<T[K]>>}`
					: K
			: never

type Path<T> = PathImpl<T, keyof T> | keyof T

type PathValue<T, P extends Path<T>> =
		P extends `${infer K}.${infer Rest}`
			? K extends keyof T
				? T[K] extends undefined
					? undefined
					: Rest extends Path<T[K]>
						? PathValue<T[K], Rest>
						: Rest extends Path<NonNullable<T[K]>>
							? PathValue<NonNullable<T[K]>, Rest> | undefined
							: never
				: never
			: P extends keyof T
				? T[P] extends undefined ? undefined : T[P]
				: never
