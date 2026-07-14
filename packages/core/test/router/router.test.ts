import { HttpResponse } from 'msw'
import { beforeEach, test } from 'vitest'
import { getRouterContext, registerHook } from '../../src'
import { HYBRIDLY_HEADER, PARTIAL_COMPONENT_HEADER, RESET_HEADER } from '../../src/constants'
import { isNavigationCancelledError } from '../../src/errors'
import { router } from '../../src/router'
import { performHybridNavigation } from '../../src/router/request/request'
import { http, server } from '../server'
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

test('sends scoped reset intent as a partial request', async ({ expect }) => {
	let partialComponent: string | undefined
	let reset: string | undefined

	server.resetHandlers(
		http.get('https://bluebird.test/reset', ({ request }) => {
			partialComponent = request.headers.get(PARTIAL_COMPONENT_HEADER) ?? undefined
			reset = request.headers.get(RESET_HEADER) ?? undefined

			return HttpResponse.json(fakePayload({ url: 'https://bluebird.test/reset' }), {
				headers: { [HYBRIDLY_HEADER]: 'true' },
			})
		}),
	)

	await performHybridNavigation({
		url: 'https://bluebird.test/reset',
		reset: ['analyses'],
	})

	expect(partialComponent).toBe('default.view')
	expect(reset).toBe('["analyses"]')
})

test('swaps the view before propagating the updated context', async ({ expect }) => {
	const calls: string[] = []

	await fakeRouterContext({
		adapter: {
			resolveComponent: async () => 'target-component',
			onViewSwap: async () => {
				calls.push('view')
			},
			onContextUpdate: (context) => {
				calls.push(`context:${context.view.component}`)
			},
		},
	})

	server.resetHandlers(
		mockSuccessfulUrl('https://bluebird.test/navigation-order', 'get', {
			json: fakePayload({
				url: 'https://bluebird.test/navigation-order',
				view: {
					component: 'target.view',
					properties: {
						foo: 'bar',
					},
				},
			}),
		}),
	)

	await performHybridNavigation({
		url: 'https://bluebird.test/navigation-order',
	})

	expect(calls.indexOf('view')).toBeLessThan(calls.indexOf('context:target.view'))
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
