import { getBulkSelectionRange } from '@hybridly/vue'
import { test } from 'vitest'

test('it resolves an inclusive bulk selection range', ({ expect }) => {
	expect(getBulkSelectionRange([1, 2, 3, 4], 1, 3)).toEqual([1, 2, 3])
	expect(getBulkSelectionRange([1, 2, 3, 4], 4, 2)).toEqual([2, 3, 4])
})

test('it falls back to the target when the anchor cannot be found', ({ expect }) => {
	expect(getBulkSelectionRange([1, 2, 3, 4], undefined, 3)).toEqual([3])
	expect(getBulkSelectionRange([1, 2, 3, 4], 5, 3)).toEqual([3])
})

test('it returns an empty range when the target cannot be found', ({ expect }) => {
	expect(getBulkSelectionRange([1, 2, 3, 4], 1, 5)).toEqual([])
})

test('it compares range entries by identity', ({ expect }) => {
	const first = { id: 1 }
	const second = { id: 2 }
	const third = { id: 3 }

	expect(getBulkSelectionRange([first, second, third], first, third)).toEqual([first, second, third])
	expect(getBulkSelectionRange([first, second, third], { id: 1 }, third)).toEqual([third])
})
