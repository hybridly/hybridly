import { describe, it, expect } from 'vitest'
import { setValueAtPath, unsetPropertyAtPath } from './utils'

describe('utils', () => {
	it('sets a value at a path in an object', () => {
		const obj = {}
		setValueAtPath(obj, 'foo.bar.baz', 'qux')
		expect(obj).toEqual({ foo: { bar: { baz: 'qux' } } })
	})

	it('unsets a property at a path in an object', () => {
		const obj = {
			foo: {
				bar: 'bar',
				baz: 'baz',
			},
		}
		unsetPropertyAtPath(obj, 'foo.bar')
		expect(obj).toEqual({
			foo: {
				baz: 'baz',
			},
		})
	})

	it('deletes empty objects along the way when unsetting a property at a path in an object', () => {
		const obj = {
			foo: {
				bar: {
					baz: 'baz',
				},
			},
		}
		unsetPropertyAtPath(obj, 'foo.bar.baz')
		expect(obj).toEqual({})
	})
})
