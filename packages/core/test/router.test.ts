// @vitest-environment happy-dom
import { beforeEach, expect, test, vi } from 'vitest'
import { initialize } from '@sleightful/core'
import { fakeRouterOptions, fakeVisit, returnsArgs } from './utils'

beforeEach(() => {
	window.location.href = 'https://localhost'
})

test('it performs a visit when initializing', async() => {
	const spy = vi.spyOn(window.history, 'pushState')

	await initialize(fakeRouterOptions())

	expect(spy).toHaveBeenCalledTimes(1)
})

test('it saves visits in the history state upon navigation', async() => {
	const spy = vi.spyOn(window.history, 'pushState').mockImplementation(returnsArgs)

	const router = await initialize(fakeRouterOptions())
	await router.navigate({
		visit: fakeVisit({
			view: {
				name: 'another-view',
				url: 'https://localhost/another-url',
			},
		}),
	})

	expect(spy).toHaveBeenCalledTimes(2)
	expect(spy.mock.results).toMatchSnapshot()
})
