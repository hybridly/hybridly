import baseMerge from 'deepmerge'
import { isPlainObject } from 'is-plain-object'
export { default as clone } from 'lodash.clonedeep'

export function random(length: number = 10): string {
	const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'

	let str = ''
	for (let i = 0; i < length; i++) {
		str += chars.charAt(Math.floor(Math.random() * chars.length))
	}

	return str
}

/** Simple pattern matching util. */
export function match<TValue extends string | number = string, TReturnValue = unknown>(
	value: TValue,
	lookup: Record<TValue | 'default', TReturnValue | ((...args: any[]) => TReturnValue)>,
	...args: any[]
): TReturnValue | Promise<TReturnValue> {
	if (value in lookup || 'default' in lookup) {
		const returnValue = value in lookup
			? lookup[value]
			: lookup.default

		return typeof returnValue === 'function' ? returnValue(...args) : returnValue
	}

	const handlers = Object.keys(lookup)
		.map((key) => `"${key}"`)
		.join(', ')

	const error = new Error(`Tried to handle "${value}" but there is no handler defined. Only defined handlers are: ${handlers}.`)

	throw error
}

export function debounce<F extends(...params: any[]) => ReturnType<F>>(fn: F, delay: number): F {
	let timeoutID: ReturnType<typeof setTimeout>
	return function(...args: unknown[]) {
		clearTimeout(timeoutID)
		timeoutID = setTimeout(() => fn(args), delay)
	} as F
}

export function value<T>(value: T | (() => T)): T {
	if (typeof value === 'function') {
		return (value as any)?.() as T
	}

	return value
}

export function when<T, D>(condition: any, data: T, _default?: D): T | D | undefined {
	if (!condition) {
		return _default
	}

	return data
}

interface MergeOptions {
	overwriteArray?: boolean
	mergePlainObjects?: boolean
	arrayMerge?: (target: any[], source: any[]) => any[]
}

export function merge<T>(x: Partial<T>, y: Partial<T>, options: MergeOptions = {}): T {
	const arrayMerge = typeof options?.arrayMerge === 'function'
		? options.arrayMerge
		: options?.overwriteArray !== false
			? (_: any, s: any) => s
			: undefined

	const isMergeableObject = options?.mergePlainObjects
		? isPlainObject
		: undefined

	return baseMerge(x, y, {
		arrayMerge,
		isMergeableObject,
	})
}

export function removeTrailingSlash(string: string): string {
	return string.replace(/\/+$/, '')
}

/**
 * Sets a value at a path in an object
 *
 * This function will set a value at a path in an object, creating any missing
 * objects along the way. The object is modified in place.
 *
 * @param obj the object to set the value in
 * @param path a dot-separated path to the property to set
 * @param value the value to set
 */
export function setValueAtPath(obj: any, path: string, value: any): void {
	// If the path doesn't contain a dot, then we can just set the value.
	if (!path.includes('.')) {
		obj[path] = value
		return
	}

	// Otherwise, we need to split the path into segments and walk down the
	// object tree until we find the right place to set the value.
	const segments = path.split('.')
	let nestedObject = obj
	for (let i = 0; i < segments.length - 1; i++) {
		const key = segments[i]
		nestedObject = nestedObject[key] = nestedObject[key] || {}
	}
	nestedObject[segments[segments.length - 1]] = value
}

/**
 * Unsets a property at a path in an object
 *
 * This function will unset a property at a path in an object, deleting any
 * objects along the way that are empty. The object is modified in place.
 *
 * @param obj the object to unset the property in
 * @param path a dot-separated path to the property to unset
 */
export function unsetPropertyAtPath(obj: any, path: string): void {
	// If the path doesn't contain a dot, then we can just delete the property.
	if (!path.includes('.')) {
		delete obj[path]
		return
	}

	// Otherwise, we need to split the path into segments and walk down the
	// object tree until we find the right place to delete the property.
	const segments = path.split('.')
	let nestedObject = obj
	for (let i = 0; i < segments.length - 1; i++) {
		const key = segments[i]
		nestedObject = nestedObject[key] = nestedObject[key] || {}
	}

	delete nestedObject[segments[segments.length - 1]]

	// If the nested object is now empty, delete it.
	if (Object.keys(nestedObject).length === 0) {
		unsetPropertyAtPath(obj, segments.slice(0, -1).join('.'))
	}
}
