import { beforeEach, test } from 'vitest'
import { getRouterContext, registerHook } from '../../src'
import { isNavigationCancelledError } from '../../src/errors'
import { router } from '../../src/router'
import { performHybridNavigation } from '../../src/router/request/request'
import { server } from '../server'
import { fakePayload, fakeRouterContext, mockSuccessfulUrl } from '../utils'

beforeEach(async () => {
	await fakeRouterContext()
})

test('performs hybrid navigations', async ({ expect }) => {
	server.resetHandlers(
		mockSuccessfulUrl('https://bluebird.test/navigation', 'get', {
			json: fakePayload({
				url: 'https://bluebird.test/navigation',
				view: {
					component: 'target.view',
					properties: {
						foo: 'bar',
					},
				},
			}),
		}),
	)

	const { response } = await performHybridNavigation({
		url: 'https://bluebird.test/navigation',
	})

	expect(response?.data).toMatchSnapshot('navigation response')
	expect(getRouterContext()).toMatchSnapshot('context after navigation')
})

test('keeps validation bags isolated when using errorBag', async ({ expect }) => {
	await fakeRouterContext({
		payload: {
			validation: {
				company: {
					name: 'Company name is required',
				},
			},
		},
	})

	server.resetHandlers(
		mockSuccessfulUrl('https://bluebird.test/validation-bag', 'post', {
			json: fakePayload({
				url: 'https://bluebird.test/validation-bag',
				validation: {
					user: {
						email: 'Invalid email address',
					},
				},
			}),
		}),
	)

	let captured: Record<string, unknown> | undefined

	await performHybridNavigation({
		url: 'https://bluebird.test/validation-bag',
		method: 'POST',
		errorBag: 'user',
		hooks: {
			'validation-error': (errors) => {
				captured = errors
			},
		},
	})

	expect(captured).toEqual({
		email: 'Invalid email address',
	})

	expect(getRouterContext().validation).toEqual({
		company: {
			name: 'Company name is required',
		},
		user: {
			email: 'Invalid email address',
		},
	})
})

test('performs external navigations', async ({ expect }) => {
	router.external('http://localhost.test/navigation', {
		owo: 'uwu',
		uwu: {
			foo: 'bar',
		},
	})

	expect(document.location.href).toBe('http://localhost.test/navigation?owo=uwu&uwu[foo]=bar')
})

test('supports global "before" event cancellation', async ({ expect }) => {
	const options = { url: 'http://localhost.test/navigation' }
	registerHook('before', () => false)

	const response = await performHybridNavigation(options)
	expect(response.error?.name).toBe('NavigationCancelledError')
	expect(isNavigationCancelledError(response.error)).toBeTruthy()
})

test('supports scoped "before" event cancellation', async ({ expect }) => {
	const options = {
		url: 'http://localhost.test/navigation',
		hooks: { before: () => false },
	}

	const response = await performHybridNavigation(options)
	expect(response.error?.name).toBe('NavigationCancelledError')
	expect(isNavigationCancelledError(response.error)).toBeTruthy()
})
