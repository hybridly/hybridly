import { beforeEach, test } from 'vitest'
import { getRouterContext } from '../../src'
import { HybridRequestOptions, MergeableProperty, Properties } from '../../src/router'
import { performHybridNavigation } from '../../src/router/request/request'
import { server } from '../server'
import { fakePayload, fakeRouterContext, mockSuccessfulUrl } from '../utils'

const preserveStateNavigationUrl = 'https://bluebird.test/preserve-state-navigation'

beforeEach(() => {
	server.resetHandlers()
})

async function performPreserveStateNavigation(parameters: {
	initialProperties: Properties
	incomingProperties: Properties
	mergeable?: MergeableProperty[]
	preserveState?: boolean | ((options: HybridRequestOptions) => boolean)
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

	server.resetHandlers(mockSuccessfulUrl(preserveStateNavigationUrl, 'get', {
		json: fakePayload({
			url: preserveStateNavigationUrl,
			view: {
				component: 'users.index',
				properties: parameters.incomingProperties,
				deferred: {},
				mergeable: parameters.mergeable ?? [],
			},
		}),
	}))

	await performHybridNavigation({
		url: preserveStateNavigationUrl,
		preserveState: parameters.preserveState,
	})

	return getRouterContext().view.properties as Record<string, unknown>
}

test('keeps omitted non-mergeable properties by default', async ({ expect }) => {
	const properties = await performPreserveStateNavigation({
		initialProperties: {
			receivedAt: '10:00:00',
			status: 'initial',
		},
		incomingProperties: {
			status: 'updated',
		},
	})

	expect(properties).toEqual({
		receivedAt: '10:00:00',
		status: 'updated',
	})
})

test('keeps omitted non-mergeable properties when preserveState is true', async ({ expect }) => {
	const properties = await performPreserveStateNavigation({
		initialProperties: {
			receivedAt: '10:00:00',
			status: 'initial',
		},
		incomingProperties: {
			status: 'updated',
		},
		preserveState: true,
	})

	expect(properties).toEqual({
		receivedAt: '10:00:00',
		status: 'updated',
	})
})

test('drops omitted non-mergeable properties when preserveState is false', async ({ expect }) => {
	const properties = await performPreserveStateNavigation({
		initialProperties: {
			receivedAt: '10:00:00',
			status: 'initial',
		},
		incomingProperties: {
			status: 'updated',
		},
		preserveState: false,
	})

	expect(properties).toEqual({
		status: 'updated',
	})
})

test('supports conditional preserveState option', async ({ expect }) => {
	const properties = await performPreserveStateNavigation({
		initialProperties: {
			receivedAt: '10:00:00',
			status: 'initial',
		},
		incomingProperties: {
			status: 'updated',
		},
		preserveState: (options) =>
			options.url === preserveStateNavigationUrl
				? false
				: true,
	})

	expect(properties).toEqual({
		status: 'updated',
	})
})

test('keeps mergeable array behavior when preserveState is false', async ({ expect }) => {
	const properties = await performPreserveStateNavigation({
		initialProperties: {
			users: [
				{ id: 1, name: 'existing-1' },
				{ id: 2, name: 'existing-2' },
			],
			status: 'initial',
		},
		incomingProperties: {
			users: [
				{ id: 2, name: 'incoming-2' },
				{ id: 3, name: 'incoming-3' },
			],
			status: 'updated',
		},
		mergeable: [['users', false, 'id', []]],
		preserveState: false,
	})

	expect(properties).toEqual({
		users: [
			{ id: 1, name: 'existing-1' },
			{ id: 2, name: 'incoming-2' },
			{ id: 3, name: 'incoming-3' },
		],
		status: 'updated',
	})
})
