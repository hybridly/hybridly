import { beforeEach, expect, test } from 'vitest'
import { current, route } from '../../src/routing'
import { fakeRouterContext } from '../utils'
import routing from './fixtures/routing.json'

beforeEach(async() => {
	await fakeRouterContext({
		routing: routing as any,
	})
})

test('the current url can be matched to routes', () => {
	location.href = route('index')
	expect(current('index')).toBe(true)

	location.href = route('chirp.show', { chirp: 1 })
	expect(current('index')).toBe(false)
	expect(current('chirp.show')).toBe(true)
	expect(current('chirp.show', { chirp: 1 })).toBe(true)
	expect(current('chirp.show', { chirp: 2 })).toBe(false)
})

test('the current url can be matched to route patterns', () => {
	location.href = route('chirp.show', { chirp: 1 })
	expect(current('index')).toBe(false)
	expect(current('chirp.*')).toBe(true)
	expect(current('chirp.*.show')).toBe(false)
	expect(current('*')).toBe(true)
	expect(current('*.show')).toBe(true)
	expect(current('*.like')).toBe(false)
})

test('the current url can be resolved to a route name', () => {
	location.href = 'https://bluebird.test/bypass-login/1'
	expect(current()).toBe('login.bypass')

	location.href = 'https://bluebird.test/abc'
	expect(current()).toBe('foo')

	location.href = 'https://bluebird.test'
	expect(current()).toBe('foo.optional')
})

test('the current url can be matched to a routes with overlapping definitions', () => {
	location.href = 'https://bluebird.test/bypass-login/'
	expect(current('foo')).toBe(true)
	expect(current('login.bypass')).toBe(true)
})

test('the current url can be matched to a route without trailing slash', () => {
	location.href = 'https://bluebird.test/bypass-login'
	expect(current('login.bypass')).toBe(true)
})

test('the current url cannot be matched to a route with only a generic parameter', () => {
	// We expect the matcher to correctly respect slashes, when matching for parameter values.
	// So it should not interpret 'bypass-login/1' as a match for the 'foo' route's parameter.
	location.href = 'https://bluebird.test/bypass-login/1'
	expect(current('foo')).toBe(false)
	expect(current('login.bypass')).toBe(true)
})

test('the current url can be matched to a route with a different fixed subdomain', () => {
	location.href = 'https://fixed.bluebird.test/'
	expect(current('fixed-subdomain')).toBe(true)
	expect(current('subdomain')).toBe(true)
})

test('the current url can be matched to a route with a parameterized subdomain', () => {
	location.href = 'https://test.bluebird.test/'
	expect(current('subdomain')).toBe(true)
	expect(current('fixed-subdomain')).toBe(false)
})

// This seems to be the behaviour of the current implementation, but it's not clear if this is the desired behaviour.
// As it seems that the port is extracted from the url, only to be used for the custom domain routes.
test('a different port config is ignored on routes without a specific domain', async() => {
	await fakeRouterContext({ routing: { ...routing as any, port: 8080 } })

	location.href = 'https://bluebird.test/'
	expect(current('index')).toBe(true)
})

test('a different port config is used for custom domain routes matching', async() => {
	await fakeRouterContext({ routing: { ...routing as any, port: 8080 } })

	location.href = 'https://fixed.bluebird.test:8080/'
	expect(current('fixed-subdomain')).toBe(true)
})

test('the current url can be matched to a route with where condition', () => {
	location.href = 'https://bluebird.test/chirps/1'
	expect(current('chirp.show')).toBe(true)
	expect(current('chirp.show', { chirp: 1 })).toBe(true)
	expect(current()).toBe('chirp.show')
	location.href = 'https://bluebird.test/chirps/abc'
	expect(current('chirp.show')).toBe(false)
})

test('the current url can be matched with parameter binding resolution', () => {
	location.href = 'https://bluebird.test/chirps/1'
	expect(current('chirp.show', { chirp: { id: 1 } })).toBe(true)
})

test('the current url can be matched to a root route with and without a trailing slash', () => {
	location.href = 'https://bluebird.test/'
	expect(current('index')).toBe(true)
	location.href = 'https://bluebird.test'
	expect(current('index')).toBe(true)
})

test('an url not in the routing table returns undefined', () => {
	location.href = 'https://bluebird.test/made/in/abyss'
	expect(current()).toBe(undefined)
})

test('providing an invalid route name will return false', () => {
	location.href = 'https://bluebird.test/'
	expect(current('__UNDEFINED__')).toBe(false)
})
