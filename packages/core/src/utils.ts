import makeDebugger from 'debug'
import baseMerge from 'deepmerge'

export const debug = {
	router: makeDebugger('hybridly:core:router'),
	history: makeDebugger('hybridly:core:history'),
	url: makeDebugger('hybridly:core:url'),
	context: makeDebugger('hybridly:core:context'),
	external: makeDebugger('hybridly:core:external'),
	scroll: makeDebugger('hybridly:core:scroll'),
	event: makeDebugger('hybridly:core:event'),
	adapter: (name: string, ...args: any[]) => makeDebugger('hybridly:adapter').extend(name)(args.shift(), ...args),
}

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

	if (Error.captureStackTrace) {
		Error.captureStackTrace(error, match)
	}

	throw error
}

export function debounce<F extends(...params: any[]) => ReturnType<F>>(fn: F, delay: number): F {
	let timeoutID: NodeJS.Timeout
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

export function clone<T>(val: T): T {
	// Thanks Anthony <3
	let k: any, out: any, tmp: any

	if (Array.isArray(val)) {
		out = Array(k = val.length)
		while (k--) {
			// eslint-disable-next-line no-cond-assign
			out[k] = (tmp = val[k]) && typeof tmp === 'object' ? clone(tmp) : tmp
		}

		return out as any
	}

	if (Object.prototype.toString.call(val) === '[object Object]') {
		out = {} // null
		// eslint-disable-next-line no-restricted-syntax
		for (k in val) {
			if (k === '__proto__') {
				Object.defineProperty(out, k, {
					value: clone((val as any)[k]),
					configurable: true,
					enumerable: true,
					writable: true,
				})
			} else {
				// eslint-disable-next-line no-cond-assign
				out[k] = (tmp = (val as any)[k]) && typeof tmp === 'object' ? clone(tmp) : tmp
			}
		}

		return out
	}

	return val
}

export function merge<T>(x: Partial<T>, y: Partial<T>): T {
	return baseMerge(x, y, { arrayMerge: (_, s) => s })
}
