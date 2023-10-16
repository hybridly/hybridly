import { beforeEach, expect, test } from 'vitest'
import { currentRouteMatches, route, getCurrentRouteName } from '../../src/routing'
import { fakeRouterContext } from '../utils'
import routing from './fixtures/routing.json'

beforeEach(async() => {
	await fakeRouterContext({
		routing: routing as any,
	})
})

test('the current url can be matched to routes', () => {
	location.href = route('index')
	expect(currentRouteMatches('index')).toBe(true)

	location.href = route('chirp.show', { chirp: 1 })
	expect(currentRouteMatches('index')).toBe(false)
	expect(currentRouteMatches('chirp.show')).toBe(true)
	expect(currentRouteMatches('chirp.show', { chirp: 1 })).toBe(true)
	expect(currentRouteMatches('chirp.show', { chirp: 2 })).toBe(false)
})

test('an exact route does not match a different route', () => {
	location.href = route('baz')
	expect(currentRouteMatches('baz.nested')).toBe(false)

	location.href = route('baz.nested')
	expect(currentRouteMatches('baz')).toBe(false)
})

test('the current url can be matched to route patterns', () => {
	location.href = route('chirp.show', { chirp: 1 })
	expect(currentRouteMatches('index')).toBe(false)
	expect(currentRouteMatches('chirp.*')).toBe(true)
	expect(currentRouteMatches('chirp.*.show')).toBe(false)
	expect(currentRouteMatches('*')).toBe(true)
	expect(currentRouteMatches('*.show')).toBe(true)
	expect(currentRouteMatches('*.like')).toBe(false)
})

test('the current url can be resolved to a route name', () => {
	location.href = 'https://bluebird.test/bypass-login/1'
	expect(getCurrentRouteName()).toBe('login.bypass')

	location.href = 'https://bluebird.test/abc'
	expect(getCurrentRouteName()).toBe('foo')

	location.href = 'https://bluebird.test'
	expect(getCurrentRouteName()).toBe('foo.optional')
})

test('the current url can be matched to a routes with overlapping definitions', () => {
	location.href = 'https://bluebird.test/bypass-login/'
	expect(currentRouteMatches('foo')).toBe(true)
	expect(currentRouteMatches('login.bypass')).toBe(true)
})

test('the current url can be matched to a route without trailing slash', () => {
	location.href = 'https://bluebird.test/bypass-login'
	expect(currentRouteMatches('login.bypass')).toBe(true)
})

test('the current url cannot be matched to a route with only a generic parameter', () => {
	// We expect the matcher to correctly respect slashes, when matching for parameter values.
	// So it should not interpret 'bypass-login/1' as a match for the 'foo' route's parameter.
	location.href = 'https://bluebird.test/bypass-login/1'
	expect(currentRouteMatches('foo')).toBe(false)
	expect(currentRouteMatches('login.bypass')).toBe(true)
})

test('the current url can be matched to a route with a different fixed subdomain', () => {
	location.href = 'https://fixed.bluebird.test/'
	expect(currentRouteMatches('fixed-subdomain')).toBe(true)
	expect(currentRouteMatches('subdomain')).toBe(true)
})

test('the current url can be matched to a route with a parameterized subdomain', () => {
	location.href = 'https://test.bluebird.test/'
	expect(currentRouteMatches('subdomain')).toBe(true)
	expect(currentRouteMatches('fixed-subdomain')).toBe(false)
})

// This seems to be the behaviour of the current implementation, but it's not clear if this is the desired behaviour.
// As it seems that the port is extracted from the url, only to be used for the custom domain routes.
test('a different port config is ignored on routes without a specific domain', async() => {
	await fakeRouterContext({ routing: { ...routing as any, port: 8080 } })

	location.href = 'https://bluebird.test/'
	expect(currentRouteMatches('index')).toBe(true)
})

test('a different port config is used for custom domain routes matching', async() => {
	await fakeRouterContext({ routing: { ...routing as any, port: 8080 } })

	location.href = 'https://fixed.bluebird.test:8080/'
	expect(currentRouteMatches('fixed-subdomain')).toBe(true)
})

test('the current url can be matched to a route with where condition', () => {
	location.href = 'https://bluebird.test/chirps/1'
	expect(currentRouteMatches('chirp.show')).toBe(true)
	expect(currentRouteMatches('chirp.show', { chirp: 1 })).toBe(true)
	expect(getCurrentRouteName()).toBe('chirp.show')
	location.href = 'https://bluebird.test/chirps/abc'
	expect(currentRouteMatches('chirp.show')).toBe(false)
})

test('the current url can be matched with parameter binding resolution', () => {
	location.href = 'https://bluebird.test/chirps/1'
	expect(currentRouteMatches('chirp.show', { chirp: { id: 1 } })).toBe(true)
})

test('the current url can be matched to a root route with and without a trailing slash', () => {
	location.href = 'https://bluebird.test/'
	expect(currentRouteMatches('index')).toBe(true)
	location.href = 'https://bluebird.test'
	expect(currentRouteMatches('index')).toBe(true)
})

test('an url not in the routing table returns undefined', () => {
	location.href = 'https://bluebird.test/made/in/abyss'
	expect(getCurrentRouteName()).toBe(undefined)
})

test('providing an invalid route name will return false', () => {
	location.href = 'https://bluebird.test/'
	expect(currentRouteMatches('__UNDEFINED__')).toBe(false)
})
