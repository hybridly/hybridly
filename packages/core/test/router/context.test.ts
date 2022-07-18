import { expect, it } from 'vitest'
import { initializeContext, setContext } from '../../src/context'
import { fakeRouterContext, makeRouterContextOptions } from '../utils'

it('creates a valid router context', () => {
	const context = initializeContext(makeRouterContextOptions({
		payload: {
			url: 'https://localhost',
			version: '123',
			view: {
				name: 'my-owo-view',
				properties: {
					foo: 'bar',
				},
			},
		},
	}))

	expect(context).toMatchSnapshot('context')
})

it('updates the context', () => {
	const context = fakeRouterContext({
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
			name: 'some.dialog',
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
