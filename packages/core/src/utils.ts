import makeDebugger from 'debug'

export const debug = {
	router: makeDebugger('sleightful:router'),
	history: makeDebugger('sleightful:history'),
	url: makeDebugger('sleightful:url'),
	context: makeDebugger('sleightful:context'),
	external: makeDebugger('sleightful:external'),
	scroll: makeDebugger('sleightful:scroll'),
	event: makeDebugger('sleightful:event'),
	adapter: (name: string, ...args: any[]) => makeDebugger('sleightful:adapter').extend(name)(args.shift(), ...args),
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
