import { beforeEach, expect, it, vi } from 'vitest'
import { performHybridNavigation } from '../../src/router/router'
import { getRouterContext, router, registerHook } from '../../src'
import { fakeRouterContext, fakePayload, mockUrl } from '../utils'

beforeEach(() => {
	fakeRouterContext()
	vi.stubGlobal('console', { warn: vi.fn() })
})

it('performs hybridly navigations', async() => {
	mockUrl('http://localhost.test/navigation', {
		json: fakePayload({
			url: 'https://localhost.test/navigation',
			view: {
				name: 'target.view',
				properties: {
					foo: 'bar',
				},
			},
		}),
	})

	const { response } = await performHybridNavigation({
		url: 'http://localhost.test/navigation',
	})

	expect(response?.data).toMatchSnapshot('navigation response')
	expect(getRouterContext()).toMatchSnapshot('context after navigation')
})

it('performs external navigations', async() => {
	router.external('http://localhost.test/navigation', {
		owo: 'uwu',
		uwu: {
			foo: 'bar',
		},
	})

	expect(document.location.href).toBe('http://localhost.test/navigation?owo=uwu&uwu[foo]=bar')
})

it('supports global "before" event cancellation', async() => {
	const options = { url: 'http://localhost.test/navigation' }
	registerHook('before', () => false)

	expect((await performHybridNavigation(options)).error?.type).toBe('NavigationCancelledError')
})

it('supports scoped "before" event cancellation', async() => {
	const options = {
		url: 'http://localhost.test/navigation',
		hooks: { before: () => false },
	}

	expect((await performHybridNavigation(options)).error?.type).toBe('NavigationCancelledError')
})
