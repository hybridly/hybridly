import { expect, test } from 'vitest'
import { initializeContext, setContext } from '../../src/context'
import { fakeRouterContext, makeRouterContextOptions } from '../utils'

test('creates a valid router context', async () => {
	const context = await initializeContext(makeRouterContextOptions({
		payload: {
			url: 'https://localhost',
			version: '123',
			view: {
				component: 'my-owo-view',
				properties: {
					foo: 'bar',
				},
			},
		},
	}))

	expect(context).toMatchSnapshot('context')
})

test('updates the context', async () => {
	const context = await fakeRouterContext({
		payload: {
			url: 'https://localhost',
		},
	})

	expect(context).toMatchSnapshot('base context')

	setContext({
		url: 'https://localhost/foo/bar',
		version: 'new-version-string',
	})

	expect(context).toMatchSnapshot('updated context')

	setContext({
		url: 'https://localhost/foo/bar/dialog',
		version: 'new-version-string',
		dialog: {
			baseUrl: 'https://base.test',
			redirectUrl: 'https://base.test/dialog',
			component: 'some.dialog',
			key: 'abc',
			properties: {
				foo: 'bar',
			},
		},
	})

	expect(context).toMatchSnapshot('updated context with dialog')

	setContext({
		url: 'https://localhost/foo',
		version: 'new-version-string',
		dialog: undefined,
	})

	expect(context).toMatchSnapshot('updated context with no dialog')
})
