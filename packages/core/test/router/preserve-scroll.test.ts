import { beforeEach, test } from 'vitest'
import { registerHook, router } from '../../src'
import type { ConditionalNavigationOption } from '../../src/router'
import { server } from '../server'
import { fakePayload, fakeRouterContext, mockSuccessfulUrl } from '../utils'

const dialogUrl = 'https://bluebird.test/users/1/edit'
const pageUrl = 'https://bluebird.test/users'

beforeEach(async () => {
	server.resetHandlers()

	await fakeRouterContext({
		adapter: {
			executeOnMounted: (callback) => callback(),
		},
	})
})

test('preserves scroll by default when navigating to a dialog', async ({ expect }) => {
	let preserveScroll: ConditionalNavigationOption<boolean> | undefined
	registerHook('navigating', (options) => {
		preserveScroll = options.preserveScroll
	})

	server.resetHandlers(
		mockSuccessfulUrl(dialogUrl, 'get', {
			json: fakePayload({
				url: dialogUrl,
				dialog: {
					baseUrl: pageUrl,
					redirectUrl: pageUrl,
					component: 'users.edit',
					key: 'users.edit:1',
					properties: {
						user: 'Tina',
					},
					deferred: {},
					mergeable: [],
				},
			}),
		}),
	)

	await router.get(dialogUrl)

	expect(preserveScroll).toBe(true)
})

test('allows scroll reset to be explicitly requested when navigating to a dialog', async ({ expect }) => {
	let preserveScroll: ConditionalNavigationOption<boolean> | undefined
	registerHook('navigating', (options) => {
		preserveScroll = options.preserveScroll
	})

	server.resetHandlers(
		mockSuccessfulUrl(dialogUrl, 'get', {
			json: fakePayload({
				url: dialogUrl,
				dialog: {
					baseUrl: pageUrl,
					redirectUrl: pageUrl,
					component: 'users.edit',
					key: 'users.edit:1',
					properties: {
						user: 'Tina',
					},
					deferred: {},
					mergeable: [],
				},
			}),
		}),
	)

	await router.get(dialogUrl, { preserveScroll: false })

	expect(preserveScroll).toBe(false)
})

test('keeps scroll reset as the default for page navigations', async ({ expect }) => {
	let preserveScroll: ConditionalNavigationOption<boolean> | undefined
	registerHook('navigating', (options) => {
		preserveScroll = options.preserveScroll
	})

	server.resetHandlers(
		mockSuccessfulUrl(pageUrl, 'get', {
			json: fakePayload({
				url: pageUrl,
				view: {
					component: 'users.index',
					properties: {},
					deferred: {},
					mergeable: [],
				},
			}),
		}),
	)

	await router.get(pageUrl)

	expect(preserveScroll).toBeUndefined()
})
