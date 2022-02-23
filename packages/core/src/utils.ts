export function match<TValue extends string | number = string, TReturnValue = unknown>(
	value: TValue,
	lookup: Record<TValue, TReturnValue | ((...args: any[]) => TReturnValue)>,
	...args: any[]
): TReturnValue | Promise<TReturnValue> {
	if (value in lookup) {
		const returnValue = lookup[value]
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
