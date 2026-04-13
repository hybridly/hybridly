import { beforeEach, test } from 'vitest'
import { getRouterContext } from '../../src'
import { MergeableProperty, Properties } from '../../src/router'
import { performHybridNavigation } from '../../src/router/request/request'
import { server } from '../server'
import { fakePayload, fakeRouterContext, mockSuccessfulUrl } from '../utils'

const mergeNavigationUrl = 'https://bluebird.test/merge-navigation'

beforeEach(() => {
	server.resetHandlers()
})

async function performMergeNavigation(parameters: {
	initialProperties: Properties
	incomingProperties: Properties
	mergeable: MergeableProperty[]
}) {
	await fakeRouterContext({
		payload: {
			url: 'https://bluebird.test/current',
			view: {
				component: 'users.index',
				properties: parameters.initialProperties,
				deferred: {},
				mergeable: [],
			},
		},
	})

	server.resetHandlers(mockSuccessfulUrl(mergeNavigationUrl, 'get', {
		json: fakePayload({
			url: mergeNavigationUrl,
			view: {
				component: 'users.index',
				properties: parameters.incomingProperties,
				deferred: {},
				mergeable: parameters.mergeable,
			},
		}),
	}))

	await performHybridNavigation({
		url: mergeNavigationUrl,
	})

	return getRouterContext().view.properties as Record<string, unknown>
}

test('appending mergeable arrays with uniqueBy gives precedence to incoming entries', async ({ expect }) => {
	const properties = await performMergeNavigation({
		initialProperties: {
			users: [
				{ meta: { id: 1 }, name: 'existing-1' },
				{ meta: { id: 2 }, name: 'existing-2' },
			],
		},
		incomingProperties: {
			users: [
				{ meta: { id: 1 }, name: 'incoming-1' },
				{ meta: { id: 3 }, name: 'incoming-3' },
			],
		},
		mergeable: [['users', false, 'meta.id', []]],
	})

	expect(properties.users).toEqual([
		{ meta: { id: 1 }, name: 'incoming-1' },
		{ meta: { id: 2 }, name: 'existing-2' },
		{ meta: { id: 3 }, name: 'incoming-3' },
	])
})

test('prepending mergeable arrays with uniqueBy keeps incoming entries first', async ({ expect }) => {
	const properties = await performMergeNavigation({
		initialProperties: {
			users: [
				{ id: 1, name: 'existing-1' },
				{ id: 2, name: 'existing-2' },
			],
		},
		incomingProperties: {
			users: [
				{ id: 1, name: 'incoming-1' },
				{ id: 3, name: 'incoming-3' },
			],
		},
		mergeable: [['users', true, 'id', []]],
	})

	expect(properties.users).toEqual([
		{ id: 1, name: 'incoming-1' },
		{ id: 3, name: 'incoming-3' },
		{ id: 2, name: 'existing-2' },
	])
})

test('append mode with uniqueBy keeps entries that do not expose the unique key', async ({ expect }) => {
	const properties = await performMergeNavigation({
		initialProperties: {
			users: [{ name: 'existing' }],
		},
		incomingProperties: {
			users: [{ name: 'incoming' }],
		},
		mergeable: [['users', false, 'id', []]],
	})

	expect(properties.users).toEqual([
		{ name: 'existing' },
		{ name: 'incoming' },
	])
})

test('appends mergeable arrays without uniqueBy', async ({ expect }) => {
	const properties = await performMergeNavigation({
		initialProperties: {
			ids: [1, 2],
		},
		incomingProperties: {
			ids: [2, 3],
		},
		mergeable: [['ids', false, null, []]],
	})

	expect(properties.ids).toEqual([1, 2, 2, 3])
})

test('merges objects recursively and applies uniqueBy on nested arrays', async ({ expect }) => {
	const properties = await performMergeNavigation({
		initialProperties: {
			feed: {
				items: [
					{ id: 1, label: 'existing-1' },
					{ id: 2, label: 'existing-2' },
				],
				meta: { page: 1 },
			},
		},
		incomingProperties: {
			feed: {
				items: [
					{ id: 1, label: 'incoming-1' },
					{ id: 3, label: 'incoming-3' },
				],
				meta: { page: 2 },
			},
		},
		mergeable: [['feed', false, 'id', []]],
	})

	expect(properties.feed).toEqual({
		items: [
			{ id: 1, label: 'incoming-1' },
			{ id: 2, label: 'existing-2' },
			{ id: 3, label: 'incoming-3' },
		],
		meta: { page: 2 },
	})
})

test('merges only configured nested merge paths inside wrapper objects', async ({ expect }) => {
	const properties = await performMergeNavigation({
		initialProperties: {
			feed: {
				data: [
					{ id: 1, label: 'existing-1' },
					{ id: 2, label: 'existing-2' },
				],
				meta: { page: 2 },
				links: [{ label: '2', active: true }],
			},
		},
		incomingProperties: {
			feed: {
				data: [
					{ id: 2, label: 'incoming-2' },
					{ id: 3, label: 'incoming-3' },
				],
				meta: { page: 3 },
				links: [{ label: '3', active: true }],
			},
		},
		mergeable: [['feed', false, 'id', ['data']]],
	})

	expect(properties.feed).toEqual({
		data: [
			{ id: 1, label: 'existing-1' },
			{ id: 2, label: 'incoming-2' },
			{ id: 3, label: 'incoming-3' },
		],
		meta: { page: 3 },
		links: [{ label: '3', active: true }],
	})
})

test('merges multiple configured nested merge paths inside wrapper objects', async ({ expect }) => {
	const properties = await performMergeNavigation({
		initialProperties: {
			feed: {
				data: [
					{ id: 1, label: 'existing-1' },
				],
				included: [
					{ id: 'a', label: 'existing-a' },
				],
				meta: { page: 1 },
			},
		},
		incomingProperties: {
			feed: {
				data: [
					{ id: 2, label: 'incoming-2' },
				],
				included: [
					{ id: 'a', label: 'incoming-a' },
					{ id: 'b', label: 'incoming-b' },
				],
				meta: { page: 2 },
			},
		},
		mergeable: [['feed', false, 'id', ['data', 'included']]],
	})

	expect(properties.feed).toEqual({
		data: [
			{ id: 1, label: 'existing-1' },
			{ id: 2, label: 'incoming-2' },
		],
		included: [
			{ id: 'a', label: 'incoming-a' },
			{ id: 'b', label: 'incoming-b' },
		],
		meta: { page: 2 },
	})
})
