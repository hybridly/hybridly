import { rest } from 'msw'
import { expect, it } from 'vitest'
import { visit } from '../../src/router/router'
import { server } from '../server'
import { fakeRouterContext, makeVisitPayload } from '../utils'

// https://github.com/mswjs/msw/issues/1125
it.skip('performs sleightful visits', async() => {
	server.use(rest.get('http://localhost.test/visit', (req, res, ctx) => res(
		ctx.status(200),
		ctx.set('x-sleightful', 'true'),
		ctx.json(makeVisitPayload({
			url: 'https://localhost',
			view: {
				name: 'target.view',
				properties: {
					foo: 'bar',
				},
			},
		})),
	)))

	const context = fakeRouterContext()
	const response = await visit(context, {
		url: 'http://localhost.test/visit',
	})

	expect(response).toMatchSnapshot('visit response')
	expect(context).toMatchSnapshot('context after visit')
})
