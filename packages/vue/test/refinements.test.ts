import { router } from '@hybridly/core'
import { beforeEach, test, vi } from 'vitest'
import { fakePayload, fakeRouterContext } from '../../core/test/utils'
import type { FilterRefinement, Refinements, SortRefinement } from '../src'
import { useRefinements } from '../src'

function makeFilter(overrides: Partial<FilterRefinement> = {}): FilterRefinement {
	return {
		name: 'status',
		hidden: false,
		label: 'Status',
		type: 'text',
		is_active: true,
		value: 'pending',
		default: 'pending',
		has_default: true,
		operator: 'equals',
		default_operator: 'equals',
		default_options: {},
		metadata: {},
		...overrides,
	}
}

function makeSort(overrides: Partial<SortRefinement> = {}): SortRefinement {
	return {
		name: 'created_at',
		hidden: false,
		label: 'Created at',
		metadata: {},
		is_active: true,
		direction: 'desc',
		default: 'desc',
		has_default: true,
		current_order: 0,
		default_order: 0,
		desc: '-created_at',
		asc: 'created_at',
		next: 'created_at',
		...overrides,
	}
}

function makeRefinements(overrides: Partial<Refinements> = {}): Refinements {
	return {
		keys: {
			filters: 'filters',
			sorts: 'sort',
			sorts_cleared: 'sort_cleared',
		},
		filters: [makeFilter()],
		sorts: [makeSort()],
		...overrides,
	}
}

beforeEach(async () => {
	vi.restoreAllMocks()

	await fakeRouterContext({
		payload: fakePayload({
			url: 'https://bluebird.test/users?view=important',
		}),
	})
})

test('applying a filter equal to its effective baseline removes the override', async ({ expect }) => {
	const reload = vi.spyOn(router, 'reload').mockResolvedValue({} as Awaited<ReturnType<typeof router.reload>>)
	const refinements = useRefinements(makeRefinements())

	await refinements.applyFilter('status', 'pending', { operator: 'equals' })

	expect(reload.mock.calls[0]?.[0]?.data).toEqual({
		filters: {
			status: undefined,
		},
	})
})

test('applying empty and null values does not clear filters implicitly', async ({ expect }) => {
	const reload = vi.spyOn(router, 'reload').mockResolvedValue({} as Awaited<ReturnType<typeof router.reload>>)
	const refinements = useRefinements(makeRefinements({
		filters: [makeFilter({ has_default: false, default: undefined })],
	}))

	await refinements.applyFilter('status', '')
	await refinements.applyFilter('status', null)

	expect(reload.mock.calls[0]?.[0]?.data).toMatchObject({
		filters: { status: { value: '' } },
	})
	expect(reload.mock.calls[1]?.[0]?.data).toMatchObject({
		filters: { status: { value: null } },
	})
})

test('updating a filter preserves unspecified effective state', async ({ expect }) => {
	const reload = vi.spyOn(router, 'reload').mockResolvedValue({} as Awaited<ReturnType<typeof router.reload>>)
	const refinements = useRefinements(makeRefinements({
		filters: [makeFilter({
			value: '2026-07-13T00:00:00Z',
			operator: 'after',
			options: { timezone: 'UTC' },
			suggestion_key: 'tomorrow',
		})],
	}))

	await refinements.updateFilter('status', { operator: 'before' })

	expect(reload.mock.calls[0]?.[0]?.data).toEqual({
		filters: {
			status: {
				value: '2026-07-13T00:00:00Z',
				disabled: undefined,
				search: undefined,
				operator: 'before',
				options: { timezone: 'UTC' },
				suggestion_key: 'tomorrow',
			},
		},
	})
})

test('applying a filter replaces unspecified state instead of patching it', async ({ expect }) => {
	const reload = vi.spyOn(router, 'reload').mockResolvedValue({} as Awaited<ReturnType<typeof router.reload>>)
	const refinements = useRefinements(makeRefinements({
		filters: [makeFilter({
			value: 'existing',
			operator: 'contains',
			options: { empty: true },
			suggestion_key: 'existing',
			default_operator: 'equals',
			has_default: false,
			default: undefined,
		})],
	}))

	await refinements.applyFilter('status', 'replacement')

	expect(reload.mock.calls[0]?.[0]?.data).toEqual({
		filters: {
			status: {
				value: 'replacement',
				disabled: undefined,
				search: undefined,
				operator: 'equals',
				options: {},
				suggestion_key: undefined,
			},
		},
	})
})

test('nullary operators submit and capture operator-only state', async ({ expect }) => {
	const reload = vi.spyOn(router, 'reload').mockResolvedValue({} as Awaited<ReturnType<typeof router.reload>>)
	const refinements = useRefinements(makeRefinements({
		filters: [makeFilter({
			value: undefined,
			operator: 'is_null',
			default: undefined,
			has_default: false,
		})],
	}))

	await refinements.applyFilter('status', 'ignored', {
		operator: 'is_null',
		suggestionKey: 'ignored',
		options: { empty: true },
	})

	expect(reload.mock.calls[0]?.[0]?.data).toEqual({
		filters: {
			status: {
				value: undefined,
				disabled: undefined,
				search: undefined,
				operator: 'is_null',
				options: undefined,
				suggestion_key: undefined,
			},
		},
	})
	expect(refinements.captureState()).toEqual({
		filters: { status: { operator: 'is_null' } },
		sorts: [{ name: 'created_at', direction: 'desc' }],
	})
})

test('applying a semantic suggestion preserves its stable key', async ({ expect }) => {
	const reload = vi.spyOn(router, 'reload').mockResolvedValue({} as Awaited<ReturnType<typeof router.reload>>)
	const refinements = useRefinements(makeRefinements({
		filters: [makeFilter({
			type: 'date',
			value: '2026-07-12T00:00:00Z',
			default: undefined,
			has_default: false,
		})],
	}))

	await refinements.applyFilter('status', '2026-07-13T00:00:00Z', { suggestionKey: 'tomorrow' })

	expect(reload.mock.calls[0]?.[0]?.data).toMatchObject({
		filters: {
			status: {
				suggestion_key: 'tomorrow',
			},
		},
	})
})

test('clearing filters uses disabled state only when a baseline exists', async ({ expect }) => {
	const reload = vi.spyOn(router, 'reload').mockResolvedValue({} as Awaited<ReturnType<typeof router.reload>>)
	const refinements = useRefinements(makeRefinements({
		filters: [
			makeFilter(),
			makeFilter({ name: 'search', has_default: false, default: undefined, value: 'query' }),
		],
	}))

	await refinements.clearFilter('status')
	await refinements.clearFilter('search')

	expect(reload.mock.calls[0]?.[0]?.data).toEqual({ filters: { status: { disabled: true } } })
	expect(reload.mock.calls[1]?.[0]?.data).toEqual({ filters: { search: undefined } })
})

test('clearing baseline sorts emits the scoped explicit clear marker', async ({ expect }) => {
	const reload = vi.spyOn(router, 'reload').mockResolvedValue({} as Awaited<ReturnType<typeof router.reload>>)
	const refinements = useRefinements(makeRefinements())

	await refinements.clearSorts()

	expect(reload.mock.calls[0]?.[0]?.data).toEqual({
		sort: undefined,
		sort_cleared: true,
	})
})

test('clearing one sort preserves its ordered siblings', async ({ expect }) => {
	const reload = vi.spyOn(router, 'reload').mockResolvedValue({} as Awaited<ReturnType<typeof router.reload>>)
	const refinements = useRefinements(makeRefinements({
		sorts: [
			makeSort({ name: 'name', direction: 'asc', current_order: 0, default: undefined, default_order: undefined, has_default: false, asc: 'name', desc: '-name', next: '-name' }),
			makeSort({ direction: 'asc', current_order: 1 }),
		],
	}))

	await refinements.getSort('name')?.clear()

	expect(reload.mock.calls[0]?.[0]?.data).toEqual({
		sort: 'created_at',
		sort_cleared: undefined,
	})
})

test('activating a sort prepends it and preserves ordered sibling sorts', async ({ expect }) => {
	const reload = vi.spyOn(router, 'reload').mockResolvedValue({} as Awaited<ReturnType<typeof router.reload>>)
	const refinements = useRefinements(makeRefinements({
		sorts: [
			makeSort({
				name: 'name',
				is_active: false,
				direction: undefined,
				current_order: undefined,
				default: undefined,
				has_default: false,
				asc: 'name',
				desc: '-name',
				next: 'name',
			}),
			makeSort(),
		],
	}))

	await refinements.toggleSort('name')

	expect(reload.mock.calls[0]?.[0]?.data).toEqual({
		sort: 'name,-created_at',
		sort_cleared: undefined,
	})
})

test('changing and removing sorts preserves their existing ordered position', async ({ expect }) => {
	const reload = vi.spyOn(router, 'reload').mockResolvedValue({} as Awaited<ReturnType<typeof router.reload>>)
	const refinements = useRefinements(makeRefinements({
		sorts: [
			makeSort({ name: 'name', direction: 'asc', current_order: 0, default: undefined, default_order: undefined, has_default: false, asc: 'name', desc: '-name', next: '-name' }),
			makeSort({ current_order: 1 }),
		],
	}))

	await refinements.toggleSort('name')
	await refinements.toggleSort('created_at', { direction: 'asc' })

	expect(reload.mock.calls[0]?.[0]?.data).toEqual({
		sort: '-name,-created_at',
		sort_cleared: undefined,
	})
	expect(reload.mock.calls[1]?.[0]?.data).toEqual({
		sort: 'name,created_at',
		sort_cleared: undefined,
	})
})

test('sort toggles restore defaults only when the complete ordered state matches', async ({ expect }) => {
	const reload = vi.spyOn(router, 'reload').mockResolvedValue({} as Awaited<ReturnType<typeof router.reload>>)
	const refinements = useRefinements(makeRefinements({
		sorts: [makeSort({ direction: 'asc', next: '-created_at' })],
	}))

	await refinements.toggleSort('created_at')

	expect(reload.mock.calls[0]?.[0]?.data).toEqual({
		sort: undefined,
		sort_cleared: undefined,
	})
})

test('captures effective filters and ordered effective sorts with exclusions', ({ expect }) => {
	const refinements = useRefinements(makeRefinements({
		filters: [
			makeFilter({
				options: { empty: true },
				suggestion_key: 'pending-period',
			}),
			makeFilter({ name: 'search', value: 'query', has_default: false, default: undefined }),
		],
		sorts: [
			makeSort({ name: 'name', direction: 'asc', current_order: 1, asc: 'name', desc: '-name' }),
			makeSort(),
		],
	}))

	expect(refinements.captureState({ exclude: ['search'] })).toEqual({
		filters: {
			status: {
				value: 'pending',
				operator: 'equals',
				options: { empty: true },
				suggestion_key: 'pending-period',
			},
		},
		sorts: [
			{ name: 'created_at', direction: 'desc' },
			{ name: 'name', direction: 'asc' },
		],
	})
})

test('detects modifications relative to effective defaults with exclusions', ({ expect }) => {
	const refinements = useRefinements(makeRefinements({
		filters: [
			makeFilter(),
			makeFilter({
				name: 'search',
				value: 'query',
				default: undefined,
				has_default: false,
			}),
		],
	}))

	expect(refinements.isModified()).toBe(true)
	expect(refinements.isModified({ exclude: ['search'] })).toBe(false)
})

test('resets overrides while preserving unrelated query parameters through merge semantics', async ({ expect }) => {
	const reload = vi.spyOn(router, 'reload').mockResolvedValue({} as Awaited<ReturnType<typeof router.reload>>)
	const refinements = useRefinements(makeRefinements())

	await refinements.resetToDefaults()

	expect(reload.mock.calls[0]?.[0]?.data).toEqual({
		filters: undefined,
		sort: undefined,
		sort_cleared: undefined,
	})
	expect(reload.mock.calls[0]?.[0]?.url).toBeUndefined()
})
