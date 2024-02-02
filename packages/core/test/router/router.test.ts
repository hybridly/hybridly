import { beforeEach, it } from 'vitest'
import { performHybridNavigation, router } from '../../src/router/router'
import { getRouterContext, registerHook } from '../../src'
import { fakeRouterContext, fakePayload, mockSuccessfulUrl } from '../utils'
import { server } from '../server'

beforeEach(async() => {
	await fakeRouterContext()
})

it('performs hybrid navigations', async({ expect }) => {
	server.resetHandlers(
		mockSuccessfulUrl({
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

it('performs external navigations', async({ expect }) => {
	router.external('http://localhost.test/navigation', {
		owo: 'uwu',
		uwu: {
			foo: 'bar',
		},
	})

	expect(document.location.href).toBe('http://localhost.test/navigation?owo=uwu&uwu[foo]=bar')
})

it('supports global "before" event cancellation', async({ expect }) => {
	const options = { url: 'http://localhost.test/navigation' }
	registerHook('before', () => false)

	expect((await performHybridNavigation(options)).error?.type).toBe('NavigationCancelledError')
})

it('supports scoped "before" event cancellation', async({ expect }) => {
	const options = {
		url: 'http://localhost.test/navigation',
		hooks: { before: () => false },
	}

	expect((await performHybridNavigation(options)).error?.type).toBe('NavigationCancelledError')
})
