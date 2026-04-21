import { getByPath } from '@clickbar/dot-diver'
import { HybridRequestOptions, route, router } from '@hybridly/core'
import type { FormDataConvertible } from '@hybridly/utils'
import type { MaybeRefOrGetter } from 'vue'
import { computed, reactive, toRaw, toValue } from 'vue'
import type { BulkSelection } from './bulk-select'
import { useBulkSelect } from './bulk-select'
import { createPaginator, type PaginatorResult } from './paginator'
import { useQueryParameters } from './query-parameters'
import type { AvailableHybridRequestOptions, Refinements, SortDirection, ToggleSortOptions, UseRefinements } from './refinements'
import { useRefinements } from './refinements'

declare global {
	interface Table<
		T extends Record<string, any> = any,
		PaginatorKind extends 'cursor' | 'length-aware' | 'simple' = 'length-aware',
	> {
		id: string
		keyName: string
		scope?: string
		columns: Column<T>[]
		inlineActions: InlineAction[]
		bulkActions: BulkAction[]
		records: Array<T>
		paginator: Omit<
			PaginatorKind extends 'cursor' ? CursorPaginator<T> : (PaginatorKind extends 'simple' ? SimplePaginator<T> : Paginator<T>),
			'data'
		>
		refinements: Refinements
		endpoint: string
	}
}

export interface Column<T extends object = never> {
	/** The name of this column. */
	name: keyof T
	/** The label of this column. */
	label: string
	/** The type of this column. */
	type: string
	/** Metadata of this column. */
	metadata: Record<string, any>
}

// #region action
export interface Action {
	/** The name of this action. */
	name: string
	/** The label of this action. */
	label: string
	/** The type of this action. */
	type: string
	/** Custom metadata for this action. */
	metadata: any
	/** A user-defined URL to which to post the action. */
	url?: string
}
// #endregion action

export interface BulkAction extends Action {
	/** Should deselect all records after action. */
	deselect: boolean
}

interface BulkActionOptions extends Omit<HybridRequestOptions, 'url'> {
	/** Force deselecting all records after action. */
	deselect?: boolean
}

interface InlineActionOptions<T> extends Omit<HybridRequestOptions, 'url'> {
	record: T
}

export interface InlineAction extends Action {
}

export type RecordIdentifier = string | number

type AsRecordTypeWithExtra<T extends Record<string, any>> = {
	[K in keyof T]: {
		extra: Record<string, any>
		value: T[K]
	}
}

export interface TableDefaultOptions extends AvailableHybridRequestOptions {
	/**
	 * Whether to include existing query parameters in the request.
	 * @default true
	 */
	includeQueryParameters?: boolean
	/**
	 * Additionnal data to send with the requests.
	 */
	data?: Record<string, FormDataConvertible> | FormDataConvertible
}

type UseTableNavigationResponse = Promise<import('@hybridly/core').NavigationResponse | undefined>

type ExtractRefValue<T> = T extends { value: infer Value } ? Value
	: T

export interface UseTableInlineActionItem<
	RecordType extends Record<string, any>,
	RecordTypeWithExtra extends Record<string, any>,
> extends InlineAction {
	/** Executes the action. */
	execute: (record: RecordTypeWithExtra | RecordIdentifier | RecordType) => UseTableNavigationResponse
}

export interface UseTableBulkActionItem extends BulkAction {
	/** Executes the action. */
	execute: (options?: BulkActionOptions) => UseTableNavigationResponse
}

export interface UseTableColumn<RecordTypeWithExtra extends Record<string, any>> extends Column<RecordTypeWithExtra> {
	/** Toggles sorting for this column. */
	toggleSort: (options?: ToggleSortOptions) => UseTableNavigationResponse
	/** Checks whether the column is being sorted. */
	isSorting: (direction?: SortDirection) => boolean
	/** Applies the filter for this column. */
	applyFilter: (value: any, options?: AvailableHybridRequestOptions) => UseTableNavigationResponse
	/** Clears the filter for this column. */
	clearFilter: (options?: AvailableHybridRequestOptions) => UseTableNavigationResponse
	/** Checks whether the column is sortable. */
	isSortable: boolean
	/** Checks whether the column is filterable. */
	isFilterable: boolean
}

export interface UseTableRecordItem<
	RecordType extends Record<string, any>,
	RecordTypeWithExtra extends Record<string, any>,
> {
	/** The actual record. */
	record: RecordType
	/** The key of the record. Use this instead of `id`. */
	key: RecordIdentifier
	/** Executes the given inline action. */
	execute: (action: string | InlineAction) => UseTableNavigationResponse
	/** Gets the available inline actions. */
	actions: Array<InlineAction & { execute: () => UseTableNavigationResponse }>
	/** Selects this record. */
	select: () => void
	/** Deselects this record. */
	deselect: () => void
	/** Toggles the selection for this record. */
	toggle: (force?: boolean) => void
	/** Checks whether this record is selected. */
	selected: boolean
	/** Gets the value of the record for the specified column. */
	value: (column: string | Column<RecordTypeWithExtra>) => any
	/** Gets the extra object of the record for the specified column. */
	extra: (column: string | Column<RecordTypeWithExtra>, path: string) => any
}

export interface UseTableReturn<
	T extends Table<any, any>,
	RecordType extends Record<string, any> = T extends Table<infer R, any> ? R : any,
	PaginatorKind extends 'cursor' | 'length-aware' | 'simple' = T extends Table<any, infer P> ? P : 'length-aware',
	RecordTypeWithExtra extends Record<string, any> = AsRecordTypeWithExtra<RecordType>,
> extends Omit<UseRefinements, 'filters' | 'sorts' | 'filtersKey' | 'sortsKey'> {
	/** Selects all records. */
	selectAll: () => void
	/** Deselects all records. */
	deselectAll: () => void
	/** Selects records on the current page. */
	selectPage: () => void
	/** Deselects records on the current page. */
	deselectPage: () => void
	/** Whether all records on the current page are selected. */
	isPageSelected: boolean
	/** Checks if the given record is selected. */
	isSelected: (record: RecordTypeWithExtra | RecordType) => boolean
	/** Whether all records are selected. */
	allSelected: boolean
	/** Whether any records are selected. */
	anySelected: boolean
	/** The current record selection. */
	selection: BulkSelection<RecordIdentifier>
	/** Binds a checkbox to its selection state. */
	bindCheckbox: (key: RecordIdentifier) => { onChange: (event: Event) => void; checked: boolean; value: RecordIdentifier }
	/** Toggles selection for the given record. */
	toggle: (record: RecordTypeWithExtra | RecordType, force?: boolean) => void
	/** Toggles selection for all records. */
	toggleAll: (force?: boolean) => void
	/** Selects selection for the given record. */
	select: (record: RecordTypeWithExtra | RecordType) => void
	/** Deselects selection for the given record. */
	deselect: (record: RecordTypeWithExtra | RecordType) => void

	/** List of inline actions for this table. */
	inlineActions: Array<UseTableInlineActionItem<RecordType, RecordTypeWithExtra>>
	/** List of bulk actions for this table. */
	bulkActions: Array<UseTableBulkActionItem>
	/** Executes the given inline action for the given record. */
	executeInlineAction: (
		action: InlineAction | string,
		options: { record: RecordTypeWithExtra | RecordIdentifier | RecordType } & Omit<HybridRequestOptions, 'url'>,
	) => UseTableNavigationResponse
	/** Executes the given bulk action. */
	executeBulkAction: (action: BulkAction | string, options?: Omit<HybridRequestOptions, 'url'> & { deselect?: boolean }) => UseTableNavigationResponse
	/** List of columns for this table. */
	columns: Array<UseTableColumn<RecordTypeWithExtra>>
	/** List of records for this table. */
	data: RecordType[]
	/** List of records for this table. */
	records: Array<UseTableRecordItem<RecordType, RecordTypeWithExtra>>
	/** Paginated meta and links. */
	paginator: PaginatorResult<RecordTypeWithExtra, Table<RecordTypeWithExtra, PaginatorKind>['paginator']>
	/** Available filters. */
	filters: ExtractRefValue<UseRefinements['filters']>
	/** Available sorts. */
	sorts: ExtractRefValue<UseRefinements['sorts']>
	/** The key for the filters. */
	filtersKey: ExtractRefValue<UseRefinements['filtersKey']>
	/** The key for the sorts. */
	sortsKey: ExtractRefValue<UseRefinements['sortsKey']>
}

/**
 * Provides utilities for working with tables.
 */
export function useTable<
	T extends Table<any, any>,
	RecordType extends Record<string, any> = T extends Table<infer R, any> ? R : any,
	PaginatorKind extends 'cursor' | 'length-aware' | 'simple' = T extends Table<any, infer P> ? P : 'length-aware',
	RecordTypeWithExtra extends Record<string, any> = AsRecordTypeWithExtra<RecordType>,
>(input: MaybeRefOrGetter<T>, defaultOptions: TableDefaultOptions = {}): UseTableReturn<T, RecordType, PaginatorKind, RecordTypeWithExtra> {
	const table = computed(() => toValue(input) as unknown as Table<RecordTypeWithExtra, PaginatorKind>)
	const bulk = useBulkSelect<RecordIdentifier>()
	const refinements = useRefinements(() => toValue(input).refinements, defaultOptions)

	/**
	 * Gets additionnal data to send with the request.
	 */
	function getAdditionnalData(options: Omit<HybridRequestOptions, 'url'>) {
		const data = {}
		options = {
			...defaultOptions,
			...options,
		}

		if (defaultOptions?.includeQueryParameters !== false) {
			Object.assign(data, structuredClone(toRaw(useQueryParameters())))
		}

		if (options?.data) {
			Object.assign(data, options.data)
		}

		return data
	}

	/**
	 * Gets the actual identifier for a record.
	 */
	function getRecordKey(record: RecordTypeWithExtra | RecordIdentifier | RecordType): RecordIdentifier {
		if (typeof record !== 'object') {
			return record
		}

		if (Reflect.has(record, '__hybridId')) {
			return Reflect.get(record, '__hybridId') as RecordIdentifier
		}

		if (!table.value.keyName) {
			throw new Error('Record key cannot be fetched because the table has no defined key.')
		}

		const value = Reflect.get(record, table.value.keyName)

		if (typeof value === 'object' && Reflect.has(value, 'value')) {
			return (value as RecordTypeWithExtra).value
		}

		return value as RecordIdentifier
	}

	function resolveInlineAction(action: InlineAction | string): undefined | InlineAction {
		if (typeof action !== 'string') {
			return action
		}

		return table.value.inlineActions.find(({ name }) => name === action)
	}

	function resolveBulkAction(action: BulkAction | string): undefined | BulkAction {
		if (typeof action !== 'string') {
			return action
		}

		return table.value.bulkActions.find(({ name }) => name === action)
	}

	function getActionUrl(action: Action, table: Table<RecordTypeWithExtra, PaginatorKind>) {
		if (action.url) {
			return action.url
		}

		return route(table.endpoint)
	}

	/**
	 * Executes the given inline action by name.
	 */
	async function executeInlineAction(
		action: InlineAction | string,
		options: InlineActionOptions<RecordTypeWithExtra | RecordIdentifier | RecordType>,
	) {
		const resolvedAction = resolveInlineAction(action)

		if (!resolvedAction) {
			console.warn(`Action [${action}] is not defined`)
			return
		}

		return await router.navigate({
			method: 'post',
			url: getActionUrl(resolvedAction, table.value),
			preserveState: true,
			data: {
				...getAdditionnalData(options),
				type: 'action:inline',
				action: resolvedAction.name,
				tableId: table.value.id,
				recordId: getRecordKey(options.record),
			},
		})
	}

	/**
	 * Executes the given bulk action for the given records.
	 */
	async function executeBulkAction(action: BulkAction | string, options: BulkActionOptions = {}) {
		const resolvedAction = resolveBulkAction(action)

		if (!resolvedAction) {
			console.warn(`Action [${action}] is not defined`)
			return
		}

		const filterParameters = refinements.currentFilters().reduce((carry, filter) => {
			return {
				...carry,
				[filter.name]: filter.value,
			}
		}, {})

		return await router.navigate({
			method: 'post',
			url: getActionUrl(resolvedAction, table.value),
			preserveState: true,
			data: {
				...getAdditionnalData(options),
				type: 'action:bulk',
				action: resolvedAction.name,
				tableId: table.value.id,
				all: bulk.selection.value.all,
				only: [...bulk.selection.value.only],
				except: [...bulk.selection.value.except],
				[refinements.filtersKey.value]: filterParameters,
			},
			hooks: {
				after: () => {
					if (options?.deselect === true || resolvedAction.deselect !== false) {
						bulk.deselectAll()
					}
				},
			},
		})
	}

	return reactive({
		/** Selects all records. */
		selectAll: bulk.selectAll,
		/** Deselects all records. */
		deselectAll: bulk.deselectAll,
		/** Selects records on the current page. */
		selectPage: () => bulk.select(...table.value.records.map((record: RecordTypeWithExtra | RecordType) => getRecordKey(record))),
		/** Deselects records on the current page. */
		deselectPage: () => bulk.deselect(...table.value.records.map((record: RecordTypeWithExtra | RecordType) => getRecordKey(record))),
		/** Whether all records on the current page are selected. */
		isPageSelected: computed(() =>
			table.value.records.length > 0
			&& table.value.records.every((record: RecordTypeWithExtra | RecordType) => bulk.selected(getRecordKey(record)))
		),
		/** Checks if the given record is selected. */
		isSelected: (record: RecordTypeWithExtra | RecordType) => bulk.selected(getRecordKey(record)),
		/** Whether all records are selected. */
		allSelected: bulk.allSelected,
		/** Whether any records is selected. */
		anySelected: bulk.anySelected,
		/** The current record selection. */
		selection: bulk.selection,
		/** Binds a checkbox to its selection state. */
		bindCheckbox: (key: RecordIdentifier) => bulk.bindCheckbox(key),
		/** Toggles selection for the given record. */
		toggle: (record: RecordTypeWithExtra | RecordType, force?: boolean) => bulk.toggle(getRecordKey(record), force),
		/** Toggles selection for all records. */
		toggleAll: (force?: boolean) => bulk.toggleAll(force),
		/** Selects selection for the given record. */
		select: (record: RecordTypeWithExtra | RecordType) => bulk.select(getRecordKey(record)),
		/** Deselects selection for the given record. */
		deselect: (record: RecordTypeWithExtra | RecordType) => bulk.deselect(getRecordKey(record)),

		/** List of inline actions for this table. */
		inlineActions: computed(() =>
			table.value.inlineActions.map((action) => ({
				/** Executes the action. */
				execute: (record: RecordTypeWithExtra | RecordIdentifier | RecordType) => executeInlineAction(action, { record }),
				...action,
			}))
		),
		/** List of bulk actions for this table. */
		bulkActions: computed(() =>
			table.value.bulkActions.map((action) => ({
				/** Executes the action. */
				execute: (options?: BulkActionOptions) => executeBulkAction(action, options),
				...action,
			}))
		),
		/** Executes the given inline action for the given record. */
		executeInlineAction,
		/** Executes the given bulk action. */
		executeBulkAction,
		/** List of columns for this table. */
		columns: computed(() =>
			table.value.columns.map((column) => ({
				...column,
				/** Toggles sorting for this column. */
				toggleSort: (options?: ToggleSortOptions) => refinements.toggleSort(column.name as string, options),
				/** Checks whether the column is being sorted. */
				isSorting: (direction?: SortDirection) => refinements.isSorting(column.name as string, direction),
				/** Applies the filer for this column. */
				applyFilter: (value: any, options?: AvailableHybridRequestOptions) => refinements.applyFilter(column.name as string, value, options),
				/** Clears the filter for this column. */
				clearFilter: (options?: AvailableHybridRequestOptions) => refinements.clearFilter(column.name as string, options),
				/** Checks whether the column is sortable. */
				isSortable: !!refinements.sorts.value.find((sort) => sort.name === column.name),
				/** Checks whether the column is filterable. */
				isFilterable: !!refinements.filters.value.find((filters) => filters.name === column.name),
			}))
		),
		/** List of records for this table. */
		data: computed(() => {
			return table.value.records.map((record) => {
				const entries = Object.entries(record)
					.map(([key, value]) => [key, value.value])
					.filter(([key]) => key !== '__hybridId')

				if (entries.length === 0) {
					return undefined
				}

				return Object.fromEntries(entries)
			}).filter(Boolean) as RecordType[]
		}),
		/** List of records for this table. */
		records: computed(() =>
			table.value.records.map((record) => {
				const entries = Object.entries(record)
					.map(([key, value]) => [key, value.value])
					.filter(([key]) => key !== '__hybridId')

				const typedRecord: RecordType = entries.length > 0
					? Object.fromEntries(entries)
					: {}

				return {
					/** The actual record. */
					record: typedRecord,
					/** The key of the record. Use this instead of `id`. */
					key: getRecordKey(record),
					/** Executes the given inline action. */
					execute: (action: string | InlineAction) => executeInlineAction(action, { record: getRecordKey(record) }),
					/** Gets the available inline actions. */
					actions: table.value.inlineActions.map((action) => ({
						...action,
						/** Executes the action. */
						execute: () => executeInlineAction(action.name, { record: getRecordKey(record) }),
					})),
					/** Selects this record. */
					select: () => bulk.select(getRecordKey(record)),
					/** Deselects this record. */
					deselect: () => bulk.deselect(getRecordKey(record)),
					/** Toggles the selection for this record. */
					toggle: (force?: boolean) => bulk.toggle(getRecordKey(record), force),
					/** Checks whether this record is selected. */
					selected: bulk.selected(getRecordKey(record)),
					/** Gets the value of the record for the specified column. */
					value: (column: string | Column<RecordTypeWithExtra>) => record[typeof column === 'string' ? column : column.name].value,
					/** Gets the extra object of the record for the specified column. */
					extra: (column: string | Column<RecordTypeWithExtra>, path: string) => getByPath(record[typeof column === 'string' ? column : column.name].extra, path),
				}
			})
		),
		/**
		 * Paginated meta and links.
		 */
		paginator: computed(() => createPaginator(table.value.paginator, defaultOptions)),
		/**
		 * Available refinements.
		 */
		...refinements,
	}) as UseTableReturn<T, RecordType, PaginatorKind, RecordTypeWithExtra>
}
