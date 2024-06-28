import { beforeEach } from 'vitest'
import { performHybridNavigation, router } from '../../src/router/router'
import { getRouterContext, registerHook } from '../../src'
import { fakePayload, fakeRouterContext, mockSuccessfulUrl } from '../utils'
import { server } from '../server'

beforeEach(async () => {
	await fakeRouterContext()
})

test('performs hybrid navigations', async ({ expect }) => {
	server.resetHandlers(
		mockSuccessfulUrl('http://localhost.test/navigation', 'get', {
			json: fakePayload({
				url: 'https://localhost.test/navigation',
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
		url: 'http://localhost.test/navigation',
	})

	expect(response?.data).toMatchSnapshot('navigation response')
	expect(getRouterContext()).toMatchSnapshot('context after navigation')
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

	expect((await performHybridNavigation(options)).error?.type).toBe('NavigationCancelledError')
})

test('supports scoped "before" event cancellation', async ({ expect }) => {
	const options = {
		url: 'http://localhost.test/navigation',
		hooks: { before: () => false },
	}

	expect((await performHybridNavigation(options)).error?.type).toBe('NavigationCancelledError')
})
