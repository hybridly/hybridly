import { beforeEach, expect, test } from 'vitest'
import { initializeContext } from '../../src/context'
import { current, route } from '../../src/routing'
import { makeRouterContextOptions } from '../utils'
import routing from './fixtures/routing.json'

beforeEach(async() => {
	await initializeContext(makeRouterContextOptions({
		routing: routing as any,
	}))
})

test('the current url can be matched against', () => {
	location.href = route('index')
	expect(current('index')).toBe(true)

	location.href = route('chirp.show', { chirp: 1 })
	expect(current('index')).toBe(true)
	expect(current('chirp.show')).toBe(true)
	expect(current('chirp.show', { chirp: 1 })).toBe(true)
	expect(current('chirp.show', { chirp: 2 })).toBe(false)
	expect(current('chirp.*')).toBe(true)
})
