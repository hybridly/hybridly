import type { HybridRequestOptions, NavigationResponse } from '@hybridly/core'
import { router } from '@hybridly/core'
import { type FormDataConvertible } from '@hybridly/utils'
import { debounce } from 'es-toolkit/function'
import type { ComputedRef, MaybeRefOrGetter, Ref } from 'vue'
import { computed, nextTick, ref, toValue, watch } from 'vue'

export type SortDirection = 'asc' | 'desc'

export type AvailableHybridRequestOptions = Omit<HybridRequestOptions, 'url' | 'data'>

export type AvailableHybridRequestOptionsForFilters = AvailableHybridRequestOptions & {
	operator?: FilterOperator
	options?: {
		/**
		 * Show empty relationships in relationship filters.
		 */
		empty?: boolean
		[key: string]: any
	}
}

export type FilterOperator =
	// General comparison operators
	| 'equals'
	| 'not_equals'
	| 'in'
	| 'not_in'
	| 'is_null'
	| 'is_not_null'
	// String operators
	| 'contains'
	| 'not_contains'
	| 'begins_with'
	| 'ends_with'
	| 'is_empty'
	| 'is_not_empty'
	// Numeric operators
	| 'greater_than'
	| 'greater_than_or_equal'
	| 'less_than'
	| 'less_than_or_equal'
	| 'between'
	| 'not_between'
	// Date operators
	| 'after'
	| 'before'
	| 'in_the_last'
	| 'not_in_the_last'

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

// #region interfaces
/**
 * Base interface for all filter refinements.
 */
export interface BaseFilterRefinement {
	/**
	 * Whether this filter is currently active.
	 */
	is_active: boolean
	/**
	 * A string-based icon identifier.
	 */
	icon?: string
	/**
	 * The type of this filter.
	 */
	type: string
	/**
	 * The label of the filter.
	 */
	label: string
	/**
	 * The name of the filter.
	 */
	name: string
	/**
	 * The current value of the filter.
	 */
	value: any
	/**
	 * The current search query of the filter.
	 */
	search_query?: string
	/**
	 * The current options of the filter.
	 */
	options?: Record<string, any>
	/**
	 * Whether this filter is hidden.
	 */
	hidden: boolean
	/**
	 * The default value of the filter.
	 */
	default: any
	/**
	 * The current operator of the filter.
	 */
	operator?: FilterOperator
	/**
	 * The default operator of the filter.
	 */
	default_operator?: FilterOperator
	/**
	 * The list of supported operators for this filter.
	 */
	supported_operators?: FilterOperator[]
	/**
	 * The metadata attributes of the filter.
	 */
	metadata: {
		/**
		 * A string-based icon identifier.
		 */
		icon?: string
		/**
		 * The label of the filter, suitable for display purposes.
		 */
		label?: string
		/**
		 * A label suitable for previewing this filter in a compact context, like a pill.
		 */
		preview_label?: string
		/**
		 * A string-based identifier suitable for previewing this filter in a compact context, like a pill.
		 */
		current_value_label?: string
		/**
		 * A string-based icon identifier suitable for displaying next to the current value of this filter in a compact context, like a pill.
		 */
		current_value_icon?: string
		/**
		 * A custom property.
		 */
		[key: string]: any
	}
}

/**
 * Text filter refinement.
 */
export interface TextFilterRefinement extends BaseFilterRefinement {
	type: 'text'
	operator?: FilterOperator
}

/**
 * Select filter refinement.
 */
export interface SelectFilterRefinement extends BaseFilterRefinement {
	type: 'select'
	operator?: FilterOperator
	metadata: BaseFilterRefinement['metadata'] & {
		/**
		 * Whether multiple options can be selected.
		 */
		is_multiple?: boolean
		/**
		 * Whether the select filter is searchable.
		 */
		is_searchable?: boolean
		/**
		 * Available options for the select filter.
		 */
		options?: Record<string | number, string>
		/**
		 * Label for the selected options.
		 */
		selected_options_label?: string
		/**
		 * Whether the filter allows an empty relationship option.
		 */
		allows_empty_relationship_option?: boolean
		/**
		 * Label for the empty relationship option.
		 */
		empty_relationship_option_label?: string
		/**
		 * Label for the empty search result.
		 */
		empty_search_result_label?: string
	}
}

/**
 * Ternary filter refinement.
 */
export interface TernaryFilterRefinement extends BaseFilterRefinement {
	type: 'ternary'
	operator?: FilterOperator
	metadata: BaseFilterRefinement['metadata'] & {
		/**
		 * Label for the true state.
		 */
		true_label?: string
		/**
		 * Label for the false state.
		 */
		false_label?: string
		/**
		 * Placeholder label.
		 */
		placeholder?: string
	}
	supported_operators?: never
}

/**
 * Boolean filter refinement.
 */
export interface BooleanFilterRefinement extends BaseFilterRefinement {
	type: 'boolean'
	operator?: FilterOperator
	metadata: BaseFilterRefinement['metadata'] & {
		/**
		 * Label for the true state.
		 */
		true_label?: string
		/**
		 * Label for the false state.
		 */
		false_label?: string
	}
}

/**
 * Numeric filter refinement.
 */
export interface NumericFilterRefinement extends BaseFilterRefinement {
	type: 'numeric'
	operator?: FilterOperator
}

/**
 * Time suggestion for single date filters.
 */
export interface TimeSuggestion {
	type: 'time'
	label: string
	date: string
}

/**
 * Timeframe suggestion for date range filters.
 */
export interface TimeframeSuggestion {
	type: 'timeframe'
	label: string
	start: string
	end: string
}

/**
 * Date filter refinement.
 */
export interface DateFilterRefinement extends BaseFilterRefinement {
	type: 'date'
	operator?: FilterOperator
	metadata: BaseFilterRefinement['metadata'] & {
		/**
		 * Whether this is a timeframe filter (with start and end columns).
		 */
		is_timeframe?: boolean
		/**
		 * The start column for timeframe filters.
		 */
		start_column?: string
		/**
		 * The end column for timeframe filters.
		 */
		end_column?: string
		/**
		 * Suggested dates or timeframes for this filter.
		 */
		suggestions?: Array<TimeSuggestion | TimeframeSuggestion>
		/**
		 * A description for this filter, suitable for display purposes.
		 */
		description?: string
	}
}

/**
 * Trashed filter refinement.
 */
export interface TrashedFilterRefinement extends BaseFilterRefinement {
	type: 'trashed'
	supported_operators?: never
}

/**
 * Callback filter refinement.
 */
export interface CallbackFilterRefinement extends BaseFilterRefinement {
	type: 'callback' | (string & {})
	supported_operators?: never
}

/**
 * Represents a filter.
 */
export type FilterRefinement =
	| TextFilterRefinement
	| SelectFilterRefinement
	| TernaryFilterRefinement
	| BooleanFilterRefinement
	| NumericFilterRefinement
	| DateFilterRefinement
	| TrashedFilterRefinement
	| CallbackFilterRefinement

export interface SortRefinement {
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

export interface Refinements {
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

/**
 * Base bound filter refinement with common methods.
 */
interface BoundFilterRefinementMethods {
	/**
	 * Applies this filter.
	 */
	apply: (value: any, options?: AvailableHybridRequestOptionsForFilters) => Promise<NavigationResponse | undefined>
	/**
	 * Clears this filter.
	 */
	clear: (options?: AvailableHybridRequestOptions) => Promise<NavigationResponse>
	/**
	 * Searches this filter.
	 */
	search: (value?: string | number, options?: AvailableHybridRequestOptions) => Promise<NavigationResponse | undefined>
	/**
	 * Whether this filter is a date filter.
	 */
	isDateFilter: () => boolean
	/**
	 * Whether this filter is a select filter.
	 */
	isSelectFilter: () => boolean
	/**
	 * Whether this filter is a search filter.
	 */
	isSearchFilter: () => boolean
	/**
	 * Whether this filter is a ternary filter.
	 */
	isTernaryFilter: () => boolean
	/**
	 * Whether this filter is a boolean filter.
	 */
	isBooleanFilter: () => boolean
	/**
	 * Whether this filter is a trashed filter.
	 */
	isTrashedFilter: () => boolean
	/**
	 * Whether this filter is a callback filter.
	 */
	isCallbackFilter: () => boolean
}

/**
 * Checks whether the given filter is a text filter, and narrows its type.
 */
export function isTextFilter(filter: FilterRefinement | BoundFilterRefinement): filter is BoundTextFilterRefinement {
	return filter.type === 'text'
}

/**
 * Checks whether the given filter is a date filter, and narrows its type.
 */
export function isDateFilter(filter: FilterRefinement | BoundFilterRefinement): filter is BoundDateFilterRefinement {
	return filter.type === 'date'
}

/**
 * Checks whether the given filter is a select filter, and narrows its type.
 */
export function isSelectFilter(filter: FilterRefinement | BoundFilterRefinement): filter is BoundSelectFilterRefinement {
	return filter.type === 'select'
}

/**
 * Checks whether the given filter is a ternary filter, and narrows its type.
 */
export function isTernaryFilter(filter: FilterRefinement | BoundFilterRefinement): filter is BoundTernaryFilterRefinement {
	return filter.type === 'ternary'
}

/**
 * Checks whether the given filter is a boolean filter, and narrows its type.
 */
export function isBooleanFilter(filter: FilterRefinement | BoundFilterRefinement): filter is BoundBooleanFilterRefinement {
	return filter.type === 'boolean'
}

/**
 * Checks whether the given filter is a trashed filter, and narrows its type.
 */
export function isTrashedFilter(filter: FilterRefinement | BoundFilterRefinement): filter is BoundTrashedFilterRefinement {
	return filter.type === 'trashed'
}

/**
 * Checks whether the given filter is a callback filter, and narrows its type.
 */
export function isCallbackFilter(filter: FilterRefinement | BoundFilterRefinement): filter is BoundCallbackFilterRefinement {
	return filter.type === 'callback' || !['text', 'select', 'ternary', 'boolean', 'trashed'].includes(filter.type)
}

/**
 * Bound text filter refinement with type-specific methods.
 */
export interface BoundTextFilterRefinement extends TextFilterRefinement, BoundFilterRefinementMethods {}

/**
 * Bound date filter refinement with type-specific methods.
 */
export interface BoundDateFilterRefinement extends DateFilterRefinement, BoundFilterRefinementMethods {}

/**
 * Bound select filter refinement with type-specific methods.
 */
export interface BoundSelectFilterRefinement extends SelectFilterRefinement, BoundFilterRefinementMethods {}

/**
 * Bound ternary filter refinement with type-specific methods.
 */
export interface BoundTernaryFilterRefinement extends TernaryFilterRefinement, BoundFilterRefinementMethods {}

/**
 * Bound boolean filter refinement with type-specific methods.
 */
export interface BoundBooleanFilterRefinement extends BooleanFilterRefinement, BoundFilterRefinementMethods {}

/**
 * Bound trashed filter refinement with type-specific methods.
 */
export interface BoundTrashedFilterRefinement extends TrashedFilterRefinement, BoundFilterRefinementMethods {}

/**
 * Bound callback filter refinement with type-specific methods.
 */
export interface BoundCallbackFilterRefinement extends CallbackFilterRefinement, BoundFilterRefinementMethods {}

/**
 * Union of all bound filter refinement types.
 */
export type BoundFilterRefinement =
	| BoundTextFilterRefinement
	| BoundSelectFilterRefinement
	| BoundTernaryFilterRefinement
	| BoundBooleanFilterRefinement
	| BoundTrashedFilterRefinement
	| BoundCallbackFilterRefinement

export interface BoundSortRefinement extends SortRefinement {
	/**
	 * Toggles this sort.
	 */
	toggle: (options?: ToggleSortOptions) => Promise<NavigationResponse | undefined>
	/**
	 * Checks if this sort is active.
	 */
	isSorting: (direction?: SortDirection) => boolean
	/**
	 * Clears this sort.
	 */
	clear: (options?: AvailableHybridRequestOptions) => Promise<NavigationResponse>
}

export interface UseRefinements {
	/**
	 * Binds a named filter to a ref, applying filters when it changes and updating the ref accordingly.
	 */
	bindFilter: <T = string | number>(name: string, options?: BindFilterOptions<T>) => Ref<string>
	/**
	 * Available filters.
	 */
	filters: ComputedRef<Array<BoundFilterRefinement>>
	/**
	 * Available sorts.
	 */
	sorts: ComputedRef<Array<BoundSortRefinement>>
	/**
	 * The key for the filters.
	 */
	filtersKey: Readonly<Ref<string>>
	/**
	 * The key for the sorts.
	 */
	sortsKey: Readonly<Ref<string>>
	/**
	 * Gets a filter by name.
	 */
	getFilter: (name: string) => BoundFilterRefinement | undefined
	/**
	 * Gets a sort by name.
	 */
	getSort: (name: string) => BoundSortRefinement | undefined
	/**
	 * Resets all filters and sorts.
	 */
	reset: (options?: AvailableHybridRequestOptions) => Promise<NavigationResponse>
	/**
	 * Toggles the specified sort.
	 */
	toggleSort: (name: string, options?: ToggleSortOptions) => Promise<NavigationResponse | undefined>
	/**
	 * Whether a sort is active.
	 */
	isSorting: (name?: string, direction?: SortDirection) => boolean
	/**
	 * Whether a filter is active.
	 */
	isFiltering: (name?: string) => boolean
	/**
	 * The current sorts.
	 */
	currentSorts: () => Array<SortRefinement>
	/**
	 * The current filters.
	 */
	currentFilters: () => Array<FilterRefinement>
	/**
	 * Clears the given filter.
	 */
	clearFilter: (filter: string, options?: AvailableHybridRequestOptions) => Promise<NavigationResponse>
	/**
	 * Resets all sorts.
	 */
	clearSorts: (options?: AvailableHybridRequestOptions) => Promise<NavigationResponse>
	/**
	 * Resets all filters.
	 */
	clearFilters: (options?: AvailableHybridRequestOptions) => Promise<NavigationResponse>
	/**
	 * Applies the given filter.
	 */
	applyFilter: (
		name: string,
		value: any,
		options?: AvailableHybridRequestOptionsForFilters,
	) => Promise<NavigationResponse | undefined>
}

export function useRefinements<T extends Refinements>(
	input: MaybeRefOrGetter<T>,
	defaultOptions: AvailableHybridRequestOptions = {},
): UseRefinements {
	const refinements = computed(() => toValue(input))
	const sortsKey = computed(() => refinements.value.keys.sorts)
	const filtersKey = computed(() => refinements.value.keys.filters)

	defaultOptions = {
		replace: false,
		...defaultOptions,
	}

	const sorts = computed(() => {
		return refinements.value.sorts.map((sort) => ({
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
		}))
	})

	const filters = computed(() => {
		return refinements.value.filters.map((filter) => ({
			...filter,
			/**
			 * Applies this filter.
			 */
			apply: (value: any, options?: AvailableHybridRequestOptionsForFilters) => applyFilter(filter.name, value, options),
			/**
			 * Clears this filter.
			 */
			clear: (options?: AvailableHybridRequestOptions) => clearFilter(filter.name, options),
			/**
			 * Searches this filter.
			 */
			search: (query?: string | number, options?: AvailableHybridRequestOptions) => searchFilter(filter.name, query, options),
			/**
			 * Whether this filter is a select filter.
			 */
			isSelectFilter: () => isSelectFilter(filter),
			/**
			 * Whether this filter is a date filter.
			 */
			isDateFilter: () => isDateFilter(filter),
			/**
			 * Whether this filter is a search filter.
			 */
			isSearchFilter: () => isTextFilter(filter),
			/**
			 * Whether this filter is a ternary filter.
			 */
			isTernaryFilter: () => isTernaryFilter(filter),
			/**
			 * Whether this filter is a boolean filter.
			 */
			isBooleanFilter: () => isBooleanFilter(filter),
			/**
			 * Whether this filter is a trashed filter.
			 */
			isTrashedFilter: () => isTrashedFilter(filter),
			/**
			 * Whether this filter is a callback filter.
			 */
			isCallbackFilter: () => isCallbackFilter(filter),
		})) as Array<BoundFilterRefinement>
	})

	function getSort(name: string): BoundSortRefinement | undefined {
		return sorts.value.find((sort) => sort.name === name)
	}

	function getFilter(name: string): BoundFilterRefinement | undefined {
		return filters.value.find((sort) => sort.name === name)
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

	async function searchFilter(name: string, query?: string | number, options: AvailableHybridRequestOptions = {}) {
		const filter = getFilter(name)

		if (!filter) {
			console.warn(`[Refinement] Filter "${name}" does not exist.`)
			return
		}

		if (['', null].includes(query?.toString() || null)) {
			query = undefined
		}

		return await router.reload({
			...defaultOptions,
			...options,
			data: {
				[filtersKey.value]: {
					[name]: {
						search: query,
					},
				},
			},
		})
	}

	async function applyFilter(name: string, value: any, options: AvailableHybridRequestOptionsForFilters = {}) {
		const filter = getFilter(name)

		if (!filter) {
			console.warn(`[Refinement] Filter "${name}" does not exist.`)
			return
		}

		if (['', null].includes(value) || value === filter.default) {
			value = undefined
		}

		const operator = (() => {
			const operator = options.operator ?? filter.operator

			if (operator === filter.default_operator) {
				return undefined
			}

			return operator
		})()

		return await router.reload({
			group: `query:${name}`,
			interruptAsyncOnStart: 'same-group',
			progress: true,
			...defaultOptions,
			...options,
			data: {
				[filtersKey.value]: {
					[name]: {
						value,
						search: undefined,
						operator,
						options: options.options ?? undefined,
					},
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

	function bindFilter<T = string | number>(name: string, options: BindFilterOptions<T> = {}): Ref<string> {
		const transform = options?.transformValue ?? ((value) => value)
		const watchFn = options?.watch ?? watch
		const getFilterValue = () => transform(refinements.value.filters.find((f) => f.name === name)?.value)
		const _proxy = ref(getFilterValue())
		let filterIsBeingApplied = false
		let proxyIsBeingUpdated = false

		// This debounced function applies the filter.
		const debouncedApplyFilter = debounce(async (value: T) => {
			await applyFilter(name, transform(value), options)
			nextTick(() => filterIsBeingApplied = false)
		}, options.debounce ?? 250)

		// This debounced function updates the `ref` value
		// according to the most recent associated value.
		const debounceUpdateProxyValue = debounce(
			() => {
				const filter = refinements.value.filters.find((f) => f.name === name)
				if (filter) {
					_proxy.value = transform(filter?.value)
				}
				nextTick(() => proxyIsBeingUpdated = false)
			},
			options.syncDebounce ?? 250,
			{ edges: ['leading'] },
		)

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

		return _proxy as Ref<string>
	}

	return {
		/**
		 * Binds a named filter to a ref, applying filters when it changes and updating the ref accordingly.
		 */
		bindFilter,
		/**
		 * Available filters.
		 */
		filters,
		/**
		 * Available sorts.
		 */
		sorts,
		/**
		 * The key for the filters.
		 */
		filtersKey,
		/**
		 * The key for the sorts.
		 */
		sortsKey,
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
