import { afterAll, beforeAll, beforeEach, describe, expect, it, vi } from 'vitest'
import { createSerializer, setHistoryState } from '../../src/router/history'
import { fakeRouterContext, makeRouterContextOptions, returnsArgs } from '../utils'

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

describe('serializer', () => {
	it('serializes and unserializes complex objects', () => {
		const options = makeRouterContextOptions()
		const serializer = createSerializer(options)
		const data = {
			set: new Set([1, 2, 3, 'string', { foo: 'bar' }]),
			foo: 'bar',
			nested: {
				foo: 'bar',
				set: new Set(['a', 'b', 1, 2, 3, 'c']),
			},
			nestedTwice: {
				foo: {
					set: new Set([1, 2, 3, 'soleil']),
				},
			},
			empty: undefined,
			map: new Map([
				['a', 'test1'],
				['b', 'test2'],
			]),
		}

		const serialized = serializer.serialize(data)

		expect(serialized).toMatchSnapshot()
		expect(data).toStrictEqual(serializer.unserialize(serialized))
	})
})
