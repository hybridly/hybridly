import { afterAll, beforeAll, describe, expect, it, vi } from 'vitest'
import { setHistoryState } from '../../src/router/history'
import { fakeRouterContext, returnsArgs } from '../utils'

describe('setHistoryState', () => {
	beforeAll(() => {
		window.location.href = 'https://localhost'
	})

	afterAll(() => {
		vi.clearAllMocks()
	})

	it('pushes the given context into the history', () => {
		const spy = vi.spyOn(window.history, 'pushState').mockImplementation(returnsArgs)

		setHistoryState(fakeRouterContext())

		expect(spy).toHaveBeenCalledOnce()
		expect(spy.mock.results).toMatchSnapshot('context')
	})

	it('replaces the given context in the current history state', () => {
		const spy = vi.spyOn(window.history, 'replaceState').mockImplementation(returnsArgs)

		setHistoryState(fakeRouterContext(), { replace: true })

		expect(spy).toHaveBeenCalledOnce()
		expect(spy.mock.results).toMatchSnapshot('context')
	})
})
