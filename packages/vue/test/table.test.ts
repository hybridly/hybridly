import { HttpResponse } from 'msw'
import { beforeEach, test, vi } from 'vitest'
import { nextTick } from 'vue'
import { useTable, type Table } from '../src'
import { http, server } from '../../core/test/server'
import { fakePayload, fakeRouterContext } from '../../core/test/utils'

interface User {
	id: number
	name: string
}

function makeTable(overrides: Partial<Table<User>> = {}): Table<User> {
	return {
		id: 'users-table',
		keyName: 'id',
		scope: undefined,
		columns: [{ name: 'name', label: 'Name', type: 'text', metadata: {} }],
		inlineActions: [{ name: 'show', label: 'Show', type: 'inline', metadata: {}, url: 'https://bluebird.test/actions' }],
		bulkActions: [{ name: 'archive', label: 'Archive', type: 'bulk', metadata: {}, deselect: true, url: 'https://bluebird.test/actions' }],
		records: [
			{ id: 1, name: 'Ada' },
			{ id: 2, name: 'Grace' },
		],
		cells: [
			{ key: 1, columns: { name: { value: 'ADA', extra: { tooltip: 'First programmer' } } } },
			{ key: 2, columns: { name: { value: 'GRACE', extra: { tooltip: 'COBOL' } } } },
		],
		paginator: {
			links: [],
			meta: {
				path: 'https://bluebird.test/users',
				from: 1,
				to: 2,
				total: 2,
				per_page: 10,
				current_page: 1,
				first_page: 1,
				last_page: 1,
				first_page_url: 'https://bluebird.test/users?page=1',
				last_page_url: 'https://bluebird.test/users?page=1',
			},
		},
		refinements: {
			keys: { filters: 'filters', sorts: 'sorts' },
			filters: [],
			sorts: [],
		},
		endpoint: 'table.actions',
		...overrides,
	}
}

beforeEach(async () => {
	server.resetHandlers()

	await fakeRouterContext({
		payload: fakePayload({
			url: 'https://bluebird.test',
			view: {
				component: 'users.index',
				properties: {},
				deferred: {},
				mergeable: [],
			},
		}),
	})
})

test('returns plain records and reads values and extras from cells', async ({ expect }) => {
	const users = useTable(makeTable())

	expect(users.data).toEqual([
		{ id: 1, name: 'Ada' },
		{ id: 2, name: 'Grace' },
	])
	expect(users.records[0].record).toEqual({ id: 1, name: 'Ada' })
	expect(users.records[0].key).toBe(1)
	expect(users.records[0].recordKey).toBe(1)
	expect(users.records[0].hasKey).toBe(true)
	expect(users.records[0].value('name')).toBe('ADA')
	expect(users.records[0].extra('name', 'tooltip')).toBe('First programmer')
})

test('uses record keys for selection and inline actions', async ({ expect }) => {
	const requests: any[] = []

	server.use(http.post('https://bluebird.test/actions', async ({ request }) => {
		requests.push(await request.json())

		return HttpResponse.json(fakePayload(), {
			headers: { 'x-hybrid': 'true' },
		})
	}))

	const users = useTable(makeTable())

	users.records[0].select()
	expect(users.records[0].selected).toBe(true)
	expect(users.selection.only.has(1)).toBe(true)

	await users.records[0].execute('show')

	expect(requests[0]).toMatchObject({
		type: 'action:inline',
		action: 'show',
		tableId: 'users-table',
		recordId: 1,
	})
})

test('ignores keyless records for selection and actions', async ({ expect }) => {
	const warn = vi.spyOn(console, 'warn').mockImplementation(() => {})
	const users = useTable(makeTable({
		keyName: null,
		inlineActions: [{ name: 'show', label: 'Show', type: 'inline', metadata: {}, url: 'https://bluebird.test/actions' }],
		bulkActions: [],
		records: [{ name: 'Keyless' } as User],
		cells: [{ key: null, columns: { name: { value: 'Keyless', extra: {} } } }],
	}))

	expect(users.records[0].key).toBe('row-0')
	expect(users.records[0].recordKey).toBeUndefined()
	expect(users.records[0].hasKey).toBe(false)

	users.records[0].select()
	await users.records[0].execute('show')
	await nextTick()

	expect(users.selection.only.size).toBe(0)
	expect(warn).toHaveBeenCalledWith('Cannot select this record because this table record has no key.')
	expect(warn).toHaveBeenCalledWith('Cannot execute an inline action because this table record has no key.')

	warn.mockRestore()
})
