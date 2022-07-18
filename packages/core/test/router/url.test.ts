import { describe, expect, it } from 'vitest'
import { fillHash, makeUrl, sameUrls } from '../../src/url'

describe('makeUrl', () => {
	it('resolves a string to an url', () => {
		const map = [
			['https://domain.test', 'https://domain.test/'],
			['https://domain.test:3000', 'https://domain.test:3000/'],
			['https://domain.test:3000#owo', 'https://domain.test:3000/#owo'],
		]

		map.forEach(([expected, actual]) => {
			expect(makeUrl(expected).toString()).toBe(actual)
		})
	})
})

describe('sameOrigin', () => {
	it('detects when urls have the same origin', () => {
		const map = [
			{ urls: ['https://domain.test', 'https://domain.test'], result: true },
			{ urls: ['https://domain.test/', 'https://domain.test'], result: true },
			{ urls: ['https://domain.test/', 'https://domain.test', 'https://domain.test'], result: true },
			{ urls: ['https://domain.test', 'https://domain.test:3000'], result: false },
			{ urls: ['https://domain.test:3000', 'https://domain.test:3000'], result: true },
			{ urls: ['https://domain.test#owo', 'https://domain.test#uwu'], result: true },
			{ urls: ['https://domain.test:3000#owo', 'https://domain.test:3000'], result: true },
			{ urls: ['https://domain.test:3001', 'https://domain.test:3002'], result: false },
		]

		map.forEach(({ urls, result }) => {
			expect(sameUrls(...urls)).toBe(result)
		})
	})
})

describe('normalizeUrl', () => {
	it('normalizes the given url', () => {
		const map = [
			['https://domain.test', 'https://domain.test/'],
			['https://domain.test:3000', 'https://domain.test:3000/'],
			['https://domain.test:3000#owo', 'https://domain.test:3000/#owo'],
		]

		map.forEach(([expected, actual]) => {
			expect(makeUrl(expected).toString()).toBe(actual)
		})
	})
})

describe('fillHash', () => {
	it('adds the hash on the target url when required', () => {
		const map = [
			{ current: 'https://localhost#hash', target: 'https://localhost', expected: 'https://localhost/#hash' },
			{ current: 'https://localhost#hash', target: 'https://distanthost', expected: 'https://distanthost/' },
			{ current: 'https://localhost', target: 'https://localhost#hash', expected: 'https://localhost/#hash' },
			{ current: 'https://localhost#hash1', target: 'https://localhost#hash2', expected: 'https://localhost/#hash2' },
		]

		map.forEach(({ current: currentUrl, target: targetUrl, expected: expectedUrl }) => {
			expect(fillHash(currentUrl, targetUrl)).toBe(expectedUrl)
		})
	})
})
