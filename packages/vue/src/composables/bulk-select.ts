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

export function useBulkSelect<T = any>() {
	const selection = ref<BulkSelection<T>>({
		all: false,
		only: new Set(),
		except: new Set(),
	})

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

	return {
		allSelected,
		selectAll,
		deselectAll,
		select,
		deselect,
		toggle,
		selected,
		selection,
	}
}
