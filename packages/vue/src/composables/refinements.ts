import type { HybridRequestOptions } from '@hybridly/core'
import { router } from '@hybridly/core'
import type { Ref } from 'vue'
import { computed, nextTick, ref, watch } from 'vue'
import { type FormDataConvertible, debounce } from '@hybridly/utils'
import { toReactive } from '../utils'

export type SortDirection = 'asc' | 'desc'

export type AvailableHybridRequestOptions = Omit<HybridRequestOptions, 'url' | 'data'>

export interface ToggleSortOptions extends AvailableHybridRequestOptions {
	direction?: SortDirection
	/** Additional sort data, only applied when sorting. */
	sortData?: { [key: string]: FormDataConvertible }
}

export interface BindFilterOptions<T> extends AvailableHybridRequestOptions {
	transformValue?: (value?: T) => any
	/** If specified, this callback will be responsible for watching the specified ref that contains the filter value.  */
	watch?: (ref: Ref<T>, cb: any) => void
	/**
	 * The debounce time in milliseconds for applying this filter.
	 * @default 250ms
	 */
	debounce?: number
	/**
	 * The debounce time in milliseconds for updating the ref.
	 * @default 250ms
	 */
	syncDebounce?: number
}

declare global {
// #region interfaces
	interface FilterRefinement {
		/**
		 * Whether this filter is currently active.
		 */
		is_active: boolean
		/**
		 * The type of this filter.
		 */
		type: 'trashed' | 'callback' | 'exact' | 'similar:loose' | 'similar:begins_with_strict' | 'similar:ends_with_strict' | string
		/**
		 * The label of the filter.
		 */
		label: string
		/**
		 * The metadata attributes of the filter.
		 */
		metadata: Record<string, any>
		/**
		 * The name of the fitler.
		 */
		name: string
		/**
		 * The current value of the filter.
		 */
		value: any
		/**
		 * Whether this filter is hidden.
		 */
		hidden: boolean
		/**
		 * The default value of the filter.
		 */
		default: any
	}

	interface SortRefinement {
		/**
		 * Whether this sort is currently active.
		 */
		is_active: boolean
		/**
		 * The current direction of the sort.
		 */
		direction?: SortDirection
		/**
		 * The default direction of the sort.
		 */
		default?: SortDirection
		/**
		 * The label of the sort.
		 */
		label: string
		/**
		 * The metadata attributes of the sort.
		 */
		metadata: Record<string, any>
		/**
		 * The name of the sort.
		 */
		name: string
		/**
		 * The value corresponding to the descending sort.
		 */
		desc: string
		/**
		 * The value corresponding to the ascending sort.
		 */
		asc: string
		/**
		 * The value that will be applied on toggle.
		 */
		next: string
		/**
		 * Whether this sort is hidden.
		 */
		hidden: boolean
	}

	interface Refinements {
		/**
		 * The list of available filters.
		 */
		filters: Array<FilterRefinement>
		/**
		 * The list of available sorts.
		 */
		sorts: Array<SortRefinement>
		/**
		 * The URL scope for these refinements.
		 */
		scope?: string
		/**
		 * The scope keys for these refinements.
		 */
		keys: {
			/**
			 * The scope key for sorting.
			 */
			sorts: string
			/**
			 * The scope key for filtering.
			 */
			filters: string
		}
	}
// #endregion interfaces
}

export function useRefinements<
	Properties extends object,
	RefinementsKey extends {
		[K in keyof Properties]: Properties[K] extends Refinements ? K : never;
	}[keyof Properties],
>(properties: Properties, refinementsKeys: RefinementsKey, defaultOptions: AvailableHybridRequestOptions = {}) {
	const refinements = computed(() => properties[refinementsKeys] as Refinements)
	const sortsKey = computed(() => refinements.value.keys.sorts)
	const filtersKey = computed(() => refinements.value.keys.filters)

	defaultOptions = {
		replace: false,
		...defaultOptions,
	}

	function getSort(name: string): SortRefinement | undefined {
		return refinements.value.sorts.find((sort) => sort.name === name)
	}

	function getFilter(name: string): FilterRefinement | undefined {
		return refinements.value.filters.find((sort) => sort.name === name)
	}

	async function reset(options: AvailableHybridRequestOptions = {}) {
		return await router.reload({
			...defaultOptions,
			...options,
			data: {
				[filtersKey.value]: undefined,
				[sortsKey.value]: undefined,
			},
		})
	}

	async function clearFilters(options: AvailableHybridRequestOptions = {}) {
		return await router.reload({
			...defaultOptions,
			...options,
			data: {
				[filtersKey.value]: undefined,
			},
		})
	}

	async function clearFilter(filter: string, options: AvailableHybridRequestOptions = {}) {
		return await router.reload({
			...defaultOptions,
			...options,
			data: {
				[filtersKey.value]: {
					[filter]: undefined,
				},
			},
		})
	}

	async function applyFilter(name: string, value: any, options: AvailableHybridRequestOptions = {}) {
		const filter = getFilter(name)

		if (!filter) {
			console.warn(`[Refinement] Filter "${name}" does not exist.`)
			return
		}

		if (['', null].includes(value) || value === filter.default) {
			value = undefined
		}

		return await router.reload({
			...defaultOptions,
			...options,
			data: {
				[filtersKey.value]: {
					[name]: value,
				},
			},
		})
	}

	async function clearSorts(options: AvailableHybridRequestOptions = {}) {
		return await router.reload({
			...defaultOptions,
			...options,
			data: {
				[sortsKey.value]: undefined,
			},
		})
	}

	function currentSorts(): Array<SortRefinement> {
		return refinements.value.sorts.filter(({ is_active }) => is_active)
	}

	function currentFilters(): Array<FilterRefinement> {
		return refinements.value.filters.filter(({ is_active }) => is_active)
	}

	function isSorting(name?: string, direction?: SortDirection): boolean {
		if (name) {
			return currentSorts().some((sort) => sort.name === name && (direction ? sort.direction === direction : true))
		}

		return currentSorts().length !== 0
	}

	function isFiltering(name?: string): boolean {
		if (name) {
			return currentFilters().some((filter) => filter.name === name)
		}

		return currentFilters().length !== 0
	}

	async function toggleSort(name: string, options?: ToggleSortOptions) {
		const sort = getSort(name)

		if (!sort) {
			console.warn(`[Refinement] Sort "${name}" does not exist.`)
			return
		}

		const next = options?.direction
			? sort[options?.direction]
			: sort.next

		const sortData = next
			? options?.sortData ?? {}
			: Object.fromEntries(Object.entries(options?.sortData ?? {}).map(([key, _]) => [key, undefined]))

		return await router.reload({
			...defaultOptions,
			...options,
			data: {
				[sortsKey.value]: next || undefined,
				...sortData,
			},
		})
	}

	function bindFilter<T = any>(name: string, options: BindFilterOptions<T> = {}) {
		const transform = options?.transformValue ?? ((value) => value)
		const watchFn = options?.watch ?? watch
		const getFilterValue = () => transform(refinements.value.filters.find((f) => f.name === name)?.value)
		const _proxy = ref(getFilterValue())
		let filterIsBeingApplied = false
		let proxyIsBeingUpdated = false

		// This debounced function applies the filter.
		const debouncedApplyFilter = debounce(options.debounce ?? 250, async (value: T) => {
			await applyFilter(name, transform(value), options)
			nextTick(() => filterIsBeingApplied = false)
		})

		// This debounced function updates the `ref` value
		// according to the most recent associated value.
		const debounceUpdateProxyValue = debounce(options.syncDebounce ?? 250, () => {
			const filter = refinements.value.filters.find((f) => f.name === name)
			if (filter) {
				_proxy.value = transform(filter?.value)
			}
			nextTick(() => proxyIsBeingUpdated = false)
		}, { atBegin: true })

		// We watch refinements instead of using the `success`
		// hook so we can handle situations where the filter
		// value is updated through another hybrid request.
		watch(() => refinements.value.filters.find((f) => f.name === name)?.value, () => {
			if (filterIsBeingApplied === true) {
				return
			}
			proxyIsBeingUpdated = true
			debounceUpdateProxyValue()
		}, { deep: true })

		// This watcher ensures that the filter is applied when the `ref`
		// changes. Under the hood, it debounces the application of
		// the filter, so it avoids spamming filter queries.
		watchFn(_proxy, async (value: T) => {
			if (proxyIsBeingUpdated === true) {
				return
			}
			filterIsBeingApplied = true
			debouncedApplyFilter(value)
		})

		return _proxy as Ref<T>
	}

	return {
		/**
		 * Binds a named filter to a ref, applying filters when it changes and updating the ref accordingly.
		 */
		bindFilter,
		/**
		 * Available filters.
		 */
		filters: toReactive(refinements.value.filters.map((filter) => ({
			...filter,
			/**
			 * Applies this filter.
			 */
			apply: (value: any, options?: AvailableHybridRequestOptions) => applyFilter(filter.name, value, options),
			/**
			 * Clears this filter.
			 */
			clear: (options?: AvailableHybridRequestOptions) => clearFilter(filter.name, options),
		}))),
		/**
		 * Available sorts.
		 */
		sorts: toReactive(refinements.value.sorts.map((sort) => ({
			...sort,
			/**
			 * Toggles this sort.
			 */
			toggle: (options?: ToggleSortOptions) => toggleSort(sort.name, options),
			/**
			 * Checks if this sort is active.
			 */
			isSorting: (direction?: SortDirection) => isSorting(sort.name, direction),
			/**
			 * Clears this sort.
			 */
			clear: (options?: AvailableHybridRequestOptions) => clearSorts(options),
		}))),
		/**
		 * The key for the filters.
		 */
		filtersKey,
		/**
		 * Gets a filter by name.
		 */
		getFilter,
		/**
		 * Gets a sort by name.
		 */
		getSort,
		/**
		 * Resets all filters and sorts.
		 */
		reset,
		/**
		 * Toggles the specified sort.
		 */
		toggleSort,
		/**
		 * Whether a sort is active.
		 */
		isSorting,
		/**
		 * Whether a filter is active.
		 */
		isFiltering,
		/**
		 * The current sorts.
		 */
		currentSorts,
		/**
		 * The current filters.
		 */
		currentFilters,
		/**
		 * Clears the given filter.
		 */
		clearFilter,
		/**
		 * Resets all sorts.
		 */
		clearSorts,
		/**
		 * Resets all filters.
		 */
		clearFilters,
		/**
		 * Applies the given filter.
		 */
		applyFilter,
	}
}
