import { computed, reactive } from 'vue'
import { route, router } from '@hybridly/core'
import { getByPath } from '@clickbar/dot-diver'
import { toReactive } from '../utils'
import { useBulkSelect } from './bulk-select'
import type { AvailableHybridRequestOptions, SortDirection, ToggleSortOptions } from './refinements'
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
		paginator: Exclude<PaginatorKind extends 'cursor' ? CursorPaginator<T> : (PaginatorKind extends 'simple' ? SimplePaginator<T> : Paginator<T>), 'data'>
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
}
// #endregion action

export interface BulkAction extends Action {
	/** Should deselect all records after action. */
	deselect: boolean
}

interface BulkActionOptions {
	/** Force deselecting all records after action. */
	deselect?: boolean
}

export interface InlineAction extends Action {
}

export type RecordIdentifier = string | number

type AsRecordType<T extends Record<string, any>> = {
	[K in keyof T]: {
		extra: Record<string, any>
		value: T[K]
	}
}

/**
 * Provides utilities for working with tables.
 */
export function useTable<
	RecordType extends(Props[PropsKey] extends Table<infer T, any> ? AsRecordType<T> : never),
	PaginatorKindName extends (Props[PropsKey] extends Table<RecordType, infer PaginatorKind> ? PaginatorKind : never),
	TableType extends (Props[PropsKey] extends Table<RecordType, PaginatorKindName> ? Table<RecordType, PaginatorKindName> : never),
	Props extends Record<string, unknown>,
	PropsKey extends keyof Props,
>(props: Props, key: PropsKey, defaultOptions: AvailableHybridRequestOptions = {}) {
	const table = computed(() => props[key] as TableType)
	const bulk = useBulkSelect<RecordIdentifier>()
	const refinements = useRefinements(toReactive(table) as any, 'refinements', defaultOptions)

	/**
	 * Gets the actual identifier for a record.
	 */
	function getRecordKey(record: RecordType | RecordIdentifier): RecordIdentifier {
		if (typeof record !== 'object') {
			return record
		}

		if (Reflect.has(record, '__hybridId')) {
			return Reflect.get(record, '__hybridId').value as any
		}

		return Reflect.get(record, table.value.keyName).value as any
	}

	function getActionName(action: Action | string): string {
		return typeof action === 'string' ? action : action.name
	}

	/**
	 * Executes the given inline action by name.
	 */
	async function executeInlineAction(action: Action | string, record: RecordType | RecordIdentifier) {
		return await router.navigate({
			method: 'post',
			url: route(table.value.endpoint),
			preserveState: true,
			data: {
				type: 'action:inline',
				action: getActionName(action),
				tableId: table.value.id,
				recordId: getRecordKey(record),
			},
		})
	}

	/**
	 * Executes the given bulk action for the given records.
	 */
	async function executeBulkAction(action: Action | string, options?: BulkActionOptions) {
		const actionName = getActionName(action)

		const filterParameters = refinements.currentFilters().reduce((carry, filter) => {
			return {
				...carry,
				[filter.name]: filter.value,
			}
		}, {})

		return await router.navigate({
			method: 'post',
			url: route(table.value.endpoint),
			preserveState: true,
			data: {
				type: 'action:bulk',
				action: actionName,
				tableId: table.value.id,
				all: bulk.selection.value.all,
				only: [...bulk.selection.value.only],
				except: [...bulk.selection.value.except],
				[refinements.filtersKey.value]: filterParameters,
			},
			hooks: {
				after: () => {
					if (options?.deselect === true || table.value.bulkActions.find(({ name }) => name === actionName)?.deselect !== false) {
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
		/** Checks if the given record is selected. */
		isSelected: (record: RecordType) => bulk.selected(getRecordKey(record)),
		/** Whether all records are selected. */
		allSelected: bulk.allSelected,
		/** The current record selection. */
		selection: bulk.selection,
		/** Toggles selection for the given record. */
		toggle: (record: RecordType) => bulk.toggle(getRecordKey(record)),
		/** Selects selection for the given record. */
		select: (record: RecordType) => bulk.select(getRecordKey(record)),
		/** Deselects selection for the given record. */
		deselect: (record: RecordType) => bulk.deselect(getRecordKey(record)),

		/** List of inline actions for this table. */
		inlineActions: computed(() => table.value.inlineActions.map((action) => ({
			/** Executes the action. */
			execute: (record: RecordType | RecordIdentifier) => executeInlineAction(action.name, record),
			...action,
		}))),
		/** List of bulk actions for this table. */
		bulkActions: computed(() => table.value.bulkActions.map((action) => ({
			/** Executes the action. */
			execute: (options?: BulkActionOptions) => executeBulkAction(action.name, options),
			...action,
		}))),
		/** Executes the given inline action for the given record. */
		executeInlineAction,
		/** Executes the given bulk action. */
		executeBulkAction,

		/** List of columns for this table. */
		columns: computed(() => table.value.columns.map((column) => ({
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
			isSortable: refinements.sorts.find((sort) => sort.name === column.name),
			/** Checks whether the column is filterable. */
			isFilterable: refinements.filters.find((filters) => filters.name === column.name),
		}))),
		/** List of records for this table. */
		records: computed(() => table.value.records.map((record) => ({
			/** The actual record. */
			record: Object.values(record).map((record) => record.value),
			/** The key of the record. Use this instead of `id`. */
			key: getRecordKey(record),
			/** Executes the given inline action. */
			execute: (action: string | InlineAction) => executeInlineAction(getActionName(action), getRecordKey(record)),
			/** Gets the available inline actions. */
			actions: table.value.inlineActions.map((action) => ({
				...action,
				/** Executes the action. */
				execute: () => executeInlineAction(action.name, getRecordKey(record)),
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
			value: (column: string | Column<RecordType>) => record[typeof column === 'string' ? column : column.name].value,
			/** Gets the extra object of the record for the specified column. */
			extra: (column: string | Column<RecordType>, path: string) => getByPath(record[typeof column === 'string' ? column : column.name].extra, path),
		}))),
		/**
		 * Paginated meta and links.
		 */
		paginator: computed(() => table.value.paginator),
		...refinements,
	})
}
