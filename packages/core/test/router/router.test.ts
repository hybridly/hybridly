import { beforeEach, expect, it, vi } from 'vitest'
import { visit } from '../../src/router/router'
import { getRouterContext, router, registerHook } from '../../src'
import { fakeRouterContext, fakeVisitPayload, mockUrl } from '../utils'

beforeEach(() => {
	fakeRouterContext()
	vi.stubGlobal('console', { warn: vi.fn() })
})

it.skip('performs hybridly visits', async() => {
	mockUrl('http://localhost.test/visit', {
		json: fakeVisitPayload({
			url: 'https://localhost.test/visit',
			view: {
				name: 'target.view',
				properties: {
					foo: 'bar',
				},
			},
		}),
	})

	const { response } = await visit({
		url: 'http://localhost.test/visit',
	})

	expect(response?.data).toMatchSnapshot('visit response')
	expect(getRouterContext()).toMatchSnapshot('context after visit')
})

it('performs external visits', async() => {
	router.external('http://localhost.test/visit', {
		owo: 'uwu',
		uwu: {
			foo: 'bar',
		},
	})

	expect(document.location.href).toBe('http://localhost.test/visit?owo=uwu&uwu[foo]=bar')
})

it('supports global "before" event cancellation', async() => {
	const options = { url: 'http://localhost.test/visit' }
	registerHook('before', () => false)

	expect((await visit(options)).error?.type).toBe('VisitCancelledError')
})

it('supports scoped "before" event cancellation', async() => {
	const options = {
		url: 'http://localhost.test/visit',
		hooks: { before: () => false },
	}

	expect((await visit(options)).error?.type).toBe('VisitCancelledError')
})
