import { expect, it } from 'vitest'
import { resolveRouter, visit } from '../../src/router/router'
import { fakeRouterContext, fakeVisitPayload, mockUrl } from '../utils'

it('performs sleightful visits', async() => {
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

	const context = fakeRouterContext()
	const { response } = await visit(context, {
		url: 'http://localhost.test/visit',
	})

	expect(response?.data).toMatchSnapshot('visit response')
	expect(context).toMatchSnapshot('context after visit')
})

it('performs external visits', async() => {
	resolveRouter(fakeRouterContext).external('http://localhost.test/visit', {
		owo: 'uwu',
		uwu: {
			foo: 'bar',
		},
	})

	expect(document.location.href).toBe('http://localhost.test/visit?owo=uwu&uwu[foo]=bar')
})

it('supports global "before" event cancellation', async() => {
	const options = { url: 'http://localhost.test/visit' }
	const context = fakeRouterContext()
	context.events.on('before', () => false)

	expect((await visit(context, options)).error?.type).toBe('VisitCancelledError')
	expect((await visit(context, options)).error?.type).toBe('VisitCancelledError')
})

it('supports scoped "before" event cancellation', async() => {
	const context = fakeRouterContext()
	const options = {
		url: 'http://localhost.test/visit',
		events: { before: () => false },
	}

	expect((await visit(context, options)).error?.type).toBe('VisitCancelledError')
})
