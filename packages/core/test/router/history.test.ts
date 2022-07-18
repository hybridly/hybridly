import { afterAll, beforeAll, beforeEach, describe, expect, it, vi } from 'vitest'
import { setHistoryState } from '../../src/router/history'
import { fakeRouterContext, returnsArgs } from '../utils'

beforeEach(() => {
	fakeRouterContext()
})

describe('setHistoryState', () => {
	beforeAll(() => {
		window.location.href = 'https://localhost'
	})

	afterAll(() => {
		vi.clearAllMocks()
	})

	it('pushes the context into the history', () => {
		const spy = vi.spyOn(window.history, 'pushState').mockImplementation(returnsArgs)

		setHistoryState()

		expect(spy).toHaveBeenCalledOnce()
		expect(spy.mock.results).toMatchSnapshot('context')
	})

	it('replaces the context in the current history state', () => {
		const spy = vi.spyOn(window.history, 'replaceState').mockImplementation(returnsArgs)

		setHistoryState({ replace: true })

		expect(spy).toHaveBeenCalledOnce()
		expect(spy.mock.results).toMatchSnapshot('context')
	})
})
