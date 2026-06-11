import type { Ref } from 'vue'
import { computed, ref } from 'vue'

// #region bulk-selection
export interface BulkSelection<T = any> {
	/** Whether all records are selected. */
	all: boolean
	/** Included records. */
	only: Set<T>
	/** Excluded records. */
	except: Set<T>
}
// #endregion bulk-selection

/**
 * Returns the inclusive range between two records in the given order.
 *
 * This is useful for implementing shift-click bulk selection: keep track of the last selected anchor,
 * then pass the currently visible record identifiers and the clicked target identifier to this helper.
 * If the target is not in the list, an empty range is returned. If the anchor is missing, only the target is returned.
 */
export function getBulkSelectionRange<T>(records: readonly T[], anchor: T | undefined, target: T): T[] {
	const targetIndex = records.indexOf(target)

	if (targetIndex === -1) {
		return []
	}

	if (anchor === undefined) {
		return [target]
	}

	const anchorIndex = records.indexOf(anchor)

	if (anchorIndex === -1) {
		return [target]
	}

	return records.slice(
		Math.min(anchorIndex, targetIndex),
		Math.max(anchorIndex, targetIndex) + 1,
	)
}

export function useBulkSelect<T = any>() {
	const selection = ref<BulkSelection<T>>({
		all: false,
		only: new Set(),
		except: new Set(),
	}) as Ref<BulkSelection<T>>

	/**
	 * Toggles selection for all records.
	 */
	function toggleAll(force?: boolean) {
		if (!selection.value.all || force === true) {
			selectAll()
		} else {
			deselectAll()
		}
	}

	/**
	 * Selects all records.
	 */
	function selectAll() {
		selection.value.all = true
		selection.value.only.clear()
		selection.value.except.clear()
	}

	/**
	 * Deselects all records.
	 */
	function deselectAll() {
		selection.value.all = false
		selection.value.only.clear()
		selection.value.except.clear()
	}

	/**
	 * Selects the given records.
	 */
	function select(...records: T[]) {
		records.forEach((record) => selection.value.except.delete(record))
		records.forEach((record) => selection.value.only.add(record))
	}

	/**
	 * Deselects the given records.
	 */
	function deselect(...records: T[]) {
		records.forEach((record) => selection.value.except.add(record))
		records.forEach((record) => selection.value.only.delete(record))
	}

	/**
	 * Toggles selection for the given records.
	 */
	function toggle(record: T, force?: boolean) {
		if (selected(record) || force === false) {
			return deselect(record)
		}

		if (!selected(record) || force === true) {
			return select(record)
		}
	}

	/**
	 * Checks whether the given record is selected.
	 */
	function selected(record: T) {
		if (selection.value.all) {
			return !selection.value.except.has(record)
		}

		return selection.value.only.has(record)
	}

	/**
	 * Checks whether all records are selected.
	 */
	const allSelected = computed(() => {
		return selection.value.all && selection.value.except.size === 0
	})

	/**
	 * Checks whether any record is selected.
	 */
	const anySelected = computed(() => {
		return selection.value.all || selection.value.only.size > 0
	})

	/**
	 * Binds a checkbox's properties.
	 */
	function bindCheckbox(key: T) {
		return {
			onChange: (event: Event) => {
				const target = event.target as HTMLInputElement
				if (target.checked) {
					select(target.value as T)
				} else {
					deselect(target.value as T)
				}
			},
			checked: selected(key),
			value: key,
		}
	}

	return {
		allSelected,
		anySelected,
		toggleAll,
		selectAll,
		deselectAll,
		select,
		deselect,
		toggle,
		selected,
		selection,
		bindCheckbox,
	}
}
