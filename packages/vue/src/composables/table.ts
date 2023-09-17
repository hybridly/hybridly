import { computed, reactive } from 'vue'
import { route, router } from 'hybridly'
import { toReactive } from '../utils'
import type { BulkSelection } from './bulk-select'
import { useBulkSelect } from './bulk-select'
import type { AvailableHybridRequestOptions, SortDirection, ToggleSortOptions } from './refinements'
import { useRefinements } from './refinements'

declare global {
	interface Table<T extends Record<string, any> = any> {
		id: string
		keyName: string
		scope?: string
		columns: Column<T>[]
		inlineActions: InlineAction[]
		bulkActions: BulkAction[]
		records: T[]
		paginator: Exclude<Paginator<T>, 'data'>
		refinements: Refinements
		endpoint: string
	}
}

export interface Column<T extends object = never> {
	/** The name of this column. */
	name: keyof T
	/** The label of this column. */
	label: string
	/** Whether this column is hidden. */
	hidden: boolean
	/** The type of this column. */
	type: string
	/** Custom metadata for this column. */
	metadata: any
}

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

/**
 * Provides utilities for working with tables.
 */
export function useTable<
	RecordType extends(Props[PropsKey] extends Table<infer RecordType> ? RecordType : never),
	TableType extends(Props[PropsKey] extends Table<RecordType> ? Table<RecordType> : never),
	Props extends object,
	PropsKey extends keyof Props,
	ColumnType extends keyof RecordType
>(props: Props, key: PropsKey) {
	const table = computed(() => props[key] as TableType)
	const bulk = useBulkSelect<RecordIdentifier>()
	const refinements = useRefinements(toReactive(table) as any, 'refinements')

	/**
	 * Gets the actual identifier for a record.
	 */
	function getRecordKey(record: RecordType | RecordIdentifier): RecordIdentifier {
		if (typeof record !== 'object') {
			return record
		}

		if (Reflect.has(record, '__hybridId')) {
			return Reflect.get(record, '__hybridId') as any
		}

		return Reflect.get(record, table.value.keyName) as any
	}

	/**
	 * Executes the given inline action by name.
	 */
	async function executeInlineAction(actionName: string, record: RecordType | RecordIdentifier) {
		return await router.navigate({
			method: 'post',
			url: route(table.value.endpoint),
			preserveState: true,
			data: {
				type: 'action:inline',
				action: actionName,
				tableId: table.value.id,
				recordId: getRecordKey(record),
			},
		})
	}

	/**
	 * Executes the given bulk action for the given records.
	 */
	async function executeBulkAction(actionName: string, selection: BulkSelection, options?: BulkActionOptions) {
		return await router.navigate({
			method: 'post',
			url: route(table.value.endpoint),
			preserveState: true,
			data: {
				type: 'action:bulk',
				action: actionName,
				tableId: table.value.id,
				all: selection.all,
				only: [...selection.only],
				except: [...selection.except],
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
		/** List of inline actions for this table. */
		inlineActions: computed(() => table.value.inlineActions),
		/** List of bulk actions for this table. */
		bulkActions: computed(() => table.value.bulkActions.map((action) => ({
			/** Executes the action. */
			execute: (options?: BulkActionOptions) => executeBulkAction(action.name, bulk.selection.value, options),
			...action,
		}))),
		/** Executes the given inline action for the given record. */
		executeInlineAction,
		/** Executes the given bulk action. */
		executeBulkAction: (action: string, options?: BulkActionOptions) => executeBulkAction(action, bulk.selection.value, options),
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
			clearFilter: (options?: ToggleSortOptions) => refinements.clearFilter(column.name as string, options),
			/** Checks whether the column is sortable. */
			isSortable: refinements.sorts.find((sort) => sort.name === column.name),
			/** Checks whether the column is filterable. */
			isFilterable: refinements.filters.find((filters) => filters.name === column.name),
		}))),
		/** List of records for this page. */
		records: computed(() => table.value.records.map((record) => ({
			/** The actual record. */
			record,
			/** The key of the record. Use this instead of `id`. */
			key: getRecordKey(record),
			/** Executes the given inline action. */
			execute: (actionName: string) => executeInlineAction(actionName, getRecordKey(record)),
			/** Gets the available inline actions. */
			actions: table.value.inlineActions,
			/** Selects this record. */
			select: () => bulk.select(getRecordKey(record)),
			/** Deselects this record. */
			deselect: () => bulk.deselect(getRecordKey(record)),
			/** Toggles the selection for this record. */
			toggle: (force?: boolean) => bulk.toggle(getRecordKey(record), force),
			/** Checks whether this record is selected. */
			selected: bulk.selected(getRecordKey(record)),
		}))),
		/**
		 * Paginated meta and links.
		 */
		paginator: computed(() => table.value.paginator),
		...refinements,
	})
}
