import { expect, it } from 'vitest'
import { visit } from '../../src/router/router'
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
