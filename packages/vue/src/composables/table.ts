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

export interface Table<
	T extends Record<string, any> = any,
	PaginatorKind extends 'cursor' | 'length-aware' | 'simple' = 'length-aware',
> {
	id: string
	keyName: keyof T | null
	scope?: string
	columns: Column<T>[]
	inlineActions: InlineAction[]
	bulkActions: BulkAction[]
	records: Array<T>
	cells: TableCellRow<T>[]
	paginator: Omit<
		PaginatorKind extends 'cursor' ? CursorPaginator<T> : (PaginatorKind extends 'simple' ? SimplePaginator<T> : Paginator<T>),
		'data'
	>
	refinements: Refinements
	endpoint: string
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

export interface TableCell<T extends object = never> {
	value: any
	extra: Record<string, any>
}

export interface TableCellRow<T extends object = never> {
	key: RecordIdentifier | null
	columns: Partial<Record<keyof T | string, TableCell<T>>>
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
> extends InlineAction {
	/** Executes the action. */
	execute: (record: RecordIdentifier | RecordType) => UseTableNavigationResponse
}

export interface UseTableBulkActionItem extends BulkAction {
	/** Executes the action. */
	execute: (options?: BulkActionOptions) => UseTableNavigationResponse
}

export interface UseTableColumn<RecordType extends Record<string, any>> extends Column<RecordType> {
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
> {
	/** The actual record. */
	record: RecordType
	/** Local key of the record. Use this as a rendering key. */
	key: RecordIdentifier
	/** Server-side key of the record, if any. */
	recordKey: RecordIdentifier | undefined
	/** Whether this record has a server-side key. */
	hasKey: boolean
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
	value: (column: string | Column<RecordType>) => any
	/** Gets the extra object of the record for the specified column. */
	extra: (column: string | Column<RecordType>, path: string) => any
}

export interface UseTableReturn<
	T extends Table<any, any>,
	RecordType extends Record<string, any> = T extends Table<infer R, any> ? R : any,
	PaginatorKind extends 'cursor' | 'length-aware' | 'simple' = T extends Table<any, infer P> ? P : 'length-aware',
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
	isSelected: (record: RecordType) => boolean
	/** Whether all records are selected. */
	allSelected: boolean
	/** Whether any records are selected. */
	anySelected: boolean
	/** The current record selection. */
	selection: BulkSelection<RecordIdentifier>
	/** Binds a checkbox to its selection state. */
	bindCheckbox: (key: RecordIdentifier) => { onChange: (event: Event) => void; checked: boolean; value: RecordIdentifier }
	/** Toggles selection for the given record. */
	toggle: (record: RecordType, force?: boolean) => void
	/** Toggles selection for all records. */
	toggleAll: (force?: boolean) => void
	/** Selects selection for the given record. */
	select: (record: RecordType) => void
	/** Deselects selection for the given record. */
	deselect: (record: RecordType) => void

	/** List of inline actions for this table. */
	inlineActions: Array<UseTableInlineActionItem<RecordType>>
	/** List of bulk actions for this table. */
	bulkActions: Array<UseTableBulkActionItem>
	/** Executes the given inline action for the given record. */
	executeInlineAction: (
		action: InlineAction | string,
		options: { record: RecordIdentifier | RecordType } & Omit<HybridRequestOptions, 'url'>,
	) => UseTableNavigationResponse
	/** Executes the given bulk action. */
	executeBulkAction: (action: BulkAction | string, options?: Omit<HybridRequestOptions, 'url'> & { deselect?: boolean }) => UseTableNavigationResponse
	/** List of columns for this table. */
	columns: Array<UseTableColumn<RecordType>>
	/** List of records for this table. */
	data: RecordType[]
	/** List of records for this table. */
	records: Array<UseTableRecordItem<RecordType>>
	/** Paginated meta and links. */
	paginator: PaginatorResult<RecordType, Table<RecordType, PaginatorKind>['paginator']>
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
>(input: MaybeRefOrGetter<T>, defaultOptions: TableDefaultOptions = {}): UseTableReturn<T, RecordType, PaginatorKind> {
	const table = computed(() => toValue(input) as unknown as Table<RecordType, PaginatorKind>)
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
	function getRecordKey(record: RecordIdentifier | RecordType): RecordIdentifier | undefined {
		if (typeof record !== 'object') {
			return record
		}

		if (!table.value.keyName) {
			return undefined
		}

		const value = Reflect.get(record, table.value.keyName)

		if (typeof value === 'string' || typeof value === 'number') {
			return value
		}

		return undefined
	}

	function getPageRecordKeys(): RecordIdentifier[] {
		return table.value.records
			.map((record) => getRecordKey(record))
			.filter((key): key is RecordIdentifier => key !== undefined)
	}

	function warnMissingRecordKey(action: string): void {
		console.warn(`Cannot ${action} because this table record has no key.`)
	}

	function getColumnName(column: string | Column<RecordType>): string {
		return typeof column === 'string' ? column : column.name as string
	}

	function getCell(index: number, column: string | Column<RecordType>): TableCell<RecordType> {
		return table.value.cells[index]?.columns[getColumnName(column)] ?? { value: undefined, extra: {} }
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

	function getActionUrl(action: Action, table: Table<RecordType, PaginatorKind>) {
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
		options: InlineActionOptions<RecordIdentifier | RecordType>,
	) {
		const resolvedAction = resolveInlineAction(action)
		const recordKey = getRecordKey(options.record)

		if (!resolvedAction) {
			console.warn(`Action [${action}] is not defined`)
			return
		}

		if (recordKey === undefined) {
			warnMissingRecordKey('execute an inline action')
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
				recordId: recordKey,
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
		selectAll: () => table.value.keyName ? bulk.selectAll() : warnMissingRecordKey('select all records'),
		/** Deselects all records. */
		deselectAll: bulk.deselectAll,
		/** Selects records on the current page. */
		selectPage: () => bulk.select(...getPageRecordKeys()),
		/** Deselects records on the current page. */
		deselectPage: () => bulk.deselect(...getPageRecordKeys()),
		/** Whether all records on the current page are selected. */
		isPageSelected: computed(() => {
			const keys = getPageRecordKeys()

			return keys.length > 0 && keys.every((key) => bulk.selected(key))
		}),
		/** Checks if the given record is selected. */
		isSelected: (record: RecordType) => {
			const key = getRecordKey(record)

			return key === undefined ? false : bulk.selected(key)
		},
		/** Whether all records are selected. */
		allSelected: bulk.allSelected,
		/** Whether any records is selected. */
		anySelected: bulk.anySelected,
		/** The current record selection. */
		selection: bulk.selection,
		/** Binds a checkbox to its selection state. */
		bindCheckbox: (key: RecordIdentifier) => bulk.bindCheckbox(key),
		/** Toggles selection for the given record. */
		toggle: (record: RecordType, force?: boolean) => {
			const key = getRecordKey(record)

			return key === undefined ? warnMissingRecordKey('toggle record selection') : bulk.toggle(key, force)
		},
		/** Toggles selection for all records. */
		toggleAll: (force?: boolean) => table.value.keyName ? bulk.toggleAll(force) : warnMissingRecordKey('toggle all records'),
		/** Selects selection for the given record. */
		select: (record: RecordType) => {
			const key = getRecordKey(record)

			return key === undefined ? warnMissingRecordKey('select this record') : bulk.select(key)
		},
		/** Deselects selection for the given record. */
		deselect: (record: RecordType) => {
			const key = getRecordKey(record)

			return key === undefined ? warnMissingRecordKey('deselect this record') : bulk.deselect(key)
		},

		/** List of inline actions for this table. */
		inlineActions: computed(() =>
			table.value.inlineActions.map((action) => ({
				/** Executes the action. */
				execute: (record: RecordIdentifier | RecordType) => executeInlineAction(action, { record }),
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
		data: computed(() => table.value.records),
		/** List of records for this table. */
		records: computed(() =>
			table.value.records.map((record, index) => {
				const recordKey = getRecordKey(record)
				const localKey = recordKey ?? `row-${index}`
				const selected = recordKey === undefined ? false : bulk.selected(recordKey)

				return {
					/** The actual record. */
					record,
					/** Local key of the record. Use this as a rendering key. */
					key: localKey,
					/** Server-side key of the record, if any. */
					recordKey,
					/** Whether this record has a server-side key. */
					hasKey: recordKey !== undefined,
					/** Executes the given inline action. */
					execute: (action: string | InlineAction) => executeInlineAction(action, { record }),
					/** Gets the available inline actions. */
					actions: table.value.inlineActions.map((action) => ({
						...action,
						/** Executes the action. */
						execute: () => executeInlineAction(action.name, { record }),
					})),
					/** Selects this record. */
					select: () => recordKey === undefined ? warnMissingRecordKey('select this record') : bulk.select(recordKey),
					/** Deselects this record. */
					deselect: () => recordKey === undefined ? warnMissingRecordKey('deselect this record') : bulk.deselect(recordKey),
					/** Toggles the selection for this record. */
					toggle: (force?: boolean) => recordKey === undefined ? warnMissingRecordKey('toggle record selection') : bulk.toggle(recordKey, force),
					/** Checks whether this record is selected. */
					selected,
					/** Gets the value of the record for the specified column. */
					value: (column: string | Column<RecordType>) => getCell(index, column).value,
					/** Gets the extra object of the record for the specified column. */
					extra: (column: string | Column<RecordType>, path: string) => getByPath(getCell(index, column).extra, path),
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
	}) as UseTableReturn<T, RecordType, PaginatorKind>
}
