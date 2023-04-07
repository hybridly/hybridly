import type { ComputedRef } from 'vue'
import { computed, readonly } from 'vue'
import { getByPath } from '@clickbar/dot-diver'
import type { Path, PathValue, SearchableObject } from '@clickbar/dot-diver'
import { state } from '../stores/state'
import { toReactive } from '../utils'

/** Accesses all current properties. */
export function useProperties<T extends object, Global extends GlobalHybridlyProperties>() {
	return readonly(toReactive(computed(() => state.context.value?.view.properties as T & Global)))
}

/** Accesses a property with a dot notation. */
export function useProperty<
	Override = never,
	T extends SearchableObject = GlobalHybridlyProperties,
	P extends Path<T> & string = Path<T> & string,
	ReturnType = [Override] extends [never] ? PathValue<T, P> : Override
>(
	path: [Override] extends [never]
		? P
		: string,
): ComputedRef<ReturnType> {
	return computed(() => getByPath(state.context.value?.view.properties as GlobalHybridlyProperties, path) as ReturnType)
}
