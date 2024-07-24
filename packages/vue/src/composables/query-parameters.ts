import { registerHook } from '@hybridly/core'
import type { MaybeRefOrGetter, Ref } from 'vue'
import { reactive, ref, toValue, watch } from 'vue'

/**
 * Access reactive query parameters.
 *
 * @see https://hybridly.dev/api/utils/use-query-parameters.html
 */
export function useQueryParameters<T extends Record<string, any> = Record<string, any>>() {
	const state: Record<string, any> = reactive({})

	function updateState() {
		const params = new URLSearchParams(window.location.search)
		const unusedKeys = new Set(Object.keys(state))
		for (const key of params.keys()) {
			const paramsForKey = params.getAll(key)
			state[key] = paramsForKey.length > 1
				? paramsForKey
				: (params.get(key) || '')
			unusedKeys.delete(key)
		}
		Array.from(unusedKeys).forEach((key) => delete state[key])
	}

	updateState()
	registerHook('after', updateState)

	return state as T
}

type RouteParameter = string | number | boolean | null | undefined
type TransformFunction<V extends RouteParameter, R> = (val: V) => R
type TransformType<T extends RouteParameter, O> =
	O extends { transform: 'number' } ? number :
		O extends { transform: 'bool' } ? boolean :
			O extends { transform: 'string' } ? string :
				O extends { transform: 'date' } ? Date :
					O extends { transform: TransformFunction<T, infer R> } ? R :
						T

interface UseQueryParameterOptions<V extends RouteParameter, R> {
	/**
	 * Specifies a default value if the query parameter does not exist.
	 */
	defaultValue?: MaybeRefOrGetter<R>

	/**
	 * Transforms the query parameter.
	 */
	transform?: 'number' | 'bool' | 'string' | 'date' | TransformFunction<V, R>
}

/**
 * Makes the specified query parameter reactive.
 *
 * @see https://hybridly.dev/api/utils/use-query-parameter.html
 */
export function useQueryParameter<
  ParameterType extends RouteParameter = RouteParameter,
  Options extends UseQueryParameterOptions<ParameterType, any> = UseQueryParameterOptions<ParameterType, ParameterType>,
>(
	name: string,
	options: Options = {} as Options,
): Ref<TransformType<ParameterType, Options>> {
	const query = useQueryParameters()

	const transform = (value: ParameterType): TransformType<ParameterType, Options> => {
		if (options.transform === 'bool') {
			return (value === true || value === 'true' || value === '1' || value === 'yes') as TransformType<ParameterType, Options>
		} else if (options.transform === 'number') {
			return Number(value) as TransformType<ParameterType, Options>
		} else if (options.transform === 'string') {
			return String(value) as TransformType<ParameterType, Options>
		} else if (options.transform === 'date') {
			return new Date(value as any) as TransformType<ParameterType, Options>
		} else if (typeof options.transform === 'function') {
			return options.transform(value) as TransformType<ParameterType, Options>
		}

		return value as TransformType<ParameterType, Options>
	}

	const value = ref<TransformType<ParameterType, Options>>()
	watch(query, () => {
		value.value = transform(query[name] ?? toValue(options.defaultValue) as ParameterType)
	}, { deep: true, immediate: true })

	return value as Ref<TransformType<ParameterType, Options>>
}
