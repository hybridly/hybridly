import type { ComputedRef } from 'vue'
import { computed, readonly, toValue } from 'vue'
import { getByPath, setByPath } from '@hybridly/utils'
import type { Path, PathValue, SearchableObject } from '@hybridly/utils'
import { state } from '../stores/state'
import { toReactive } from '../utils'

/** Accesses all current properties. */
export function useProperties<T extends object, Global extends GlobalHybridlyProperties = GlobalHybridlyProperties>() {
	return readonly(toReactive(computed(() => state.properties.value as T & Global)))
}

/** Accesses a property with a dot notation. */
export function useProperty<
	Override = never,
	T extends SearchableObject = GlobalHybridlyProperties,
	P extends Path<T> & string = Path<T> & string,
	ReturnType = [Override] extends [never] ? PathValue<T, P> : Override,
>(
	path: [Override] extends [never]
		? P
		: string,
): ComputedRef<ReturnType> {
	return computed(() => getByPath(state.properties.value as GlobalHybridlyProperties, path) as ReturnType)
}

/**
 * Sets the property at the given path to the given value.
 * Note: this helper is experimental and may change in the future.
 */
export function setProperty<
	Override = never,
	T extends SearchableObject = GlobalHybridlyProperties,
	P extends Path<T> & string = Path<T> & string,
	ValueType = [Override] extends [never] ? PathValue<T, P> : Override,
>(
	path: [Override] extends [never] ? P : string,
	value: ValueType,
): void {
	if (!state.properties.value) {
		return
	}

	setByPath(state.properties.value, path, toValue(value as any))

	if (state.context.value?.view.properties) {
		setByPath(state.context.value.view.properties as GlobalHybridlyProperties, path, toValue(value as any))
	}
}
