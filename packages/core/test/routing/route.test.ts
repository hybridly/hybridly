import { beforeEach, describe, expect, it, vi } from 'vitest'
import { initializeContext } from '../../src/context'
import { route } from '../../src/routing/index'
import { makeRouterContextOptions } from '../utils'
import routing from './fixtures/routing.json'

beforeEach(async () => {
	await initializeContext(makeRouterContextOptions({
		routing: routing as any,
	}))
})

describe('an url can be generated from a route name', () => {
	it('without parameter', () => {
		expect(route('index')).toBe('https://bluebird.test')
		expect(route('index', {}, false)).toBe('')
	})

	it('with required parameter', () => {
		expect(route('chirp.show', { chirp: 10 })).toBe('https://bluebird.test/chirps/10')
		expect(route('chirp.show', { chirp: 10 }, false)).toBe('/chirps/10')
		expect(route('subdomain', { subdomain: 'demo' })).toBe('https://demo.bluebird.test')
	})

	it('throws when required parameter is missing', () => {
		expect(() => route('chirp.show')).toThrowError('Parameter [chirp] is required for route [chirp.show].')
	})

	it('warns when the required parameter does not respect where constraints', () => {
		const messages: string[] = []
		vi.stubGlobal('console', {
			warn: vi.fn((message: string) => messages.push(message)),
		})

		// Such an error would be better handled by the back-end and the types rather than a runtime check
		expect(route('chirp.show', { chirp: 'foobar' })).toBe('https://bluebird.test/chirps/foobar')
		expect(route('chirp.show', { chirp: 'foobar' }, false)).toBe('/chirps/foobar')

		expect(messages.includes('Parameter [chirp] does not match the required format [[0-9]+] for route [chirp.show]'))
	})

	it('with additional parameters', () => {
		expect(route('index', { foo: 'bar', bar: ['baz', 'boo'], owo: 69 })).toBe('https://bluebird.test?foo=bar&bar[0]=baz&bar[1]=boo&owo=69')
		expect(route('index', { foo: 'bar', bar: ['baz', 'boo'], owo: 69 }, false)).toBe('?foo=bar&bar[0]=baz&bar[1]=boo&owo=69')
	})

	it('with both additional and required parameters', () => {
		expect(route('chirp.show', { chirp: 10, foo: 'bar' })).toBe('https://bluebird.test/chirps/10?foo=bar')
		expect(route('chirp.show', { chirp: 10, foo: 'bar' }, false)).toBe('/chirps/10?foo=bar')
	})

	it('default parameters are added automatically', () => {
		expect(route('foo')).toBe('https://bluebird.test/baz')
	})

	it('default parameters can be overwritten', () => {
		expect(route('foo', { bar: 'owo' })).toBe('https://bluebird.test/owo')
	})

	it('default optional parameters can be omitted and are added automatically', () => {
		expect(route('foo.optional')).toBe('https://bluebird.test/baz')
	})

	it('default optional parameters can be overwritten', () => {
		expect(route('foo.optional', { bar: 'owo' })).toBe('https://bluebird.test/owo')
	})

	it('optional parameters can be omitted', () => {
		expect(route('optional')).toBe('https://bluebird.test')
	})

	it('optional parameters can be used', () => {
		expect(route('optional', { test: 'foobar' })).toBe('https://bluebird.test/foobar')
	})

	it('objects can be used when bindings are defined', () => {
		const bar = { id: 'owo' }
		expect(route('foo', { bar })).toBe('https://bluebird.test/owo')
	})

	it('foo', async () => {
		await initializeContext(makeRouterContextOptions({
			routing: {
				url: 'https://bluebird.test',
				defaults: {
					bar: 'baz',
				},
				routes: {
					test: {
						method: ['GET'],
						uri: 'test/{page?}',
						name: 'test',
					},
				},
			},
		}))

		expect(route('test', { foo: 'bar', baz: 'baz' })).toBe('https://bluebird.test/test?foo=bar&baz=baz')
	})
})
