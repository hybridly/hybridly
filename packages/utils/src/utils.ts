import baseMerge from 'deepmerge'
import { isPlainObject } from 'es-toolkit/predicate'
export { getByPath, type Path, type PathValue, type SearchableObject, setByPath } from '@clickbar/dot-diver'

export function random(length: number = 10): string {
	const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'

	let str = ''
	for (let i = 0; i < length; i++) {
		str += chars.charAt(Math.floor(Math.random() * chars.length))
	}

	return str
}

/** Simple pattern matching util. */
export function match<TValue extends string | number = string, TReturnValue = unknown, TArgs extends readonly unknown[] = []>(
	value: TValue,
	lookup: Record<TValue | 'default', TReturnValue | ((...args: TArgs) => TReturnValue | Promise<TReturnValue>)>,
	...args: TArgs
): TReturnValue | Promise<TReturnValue> {
	if (value in lookup || 'default' in lookup) {
		const returnValue = (value in lookup ? lookup[value] : lookup.default) as TReturnValue | ((...args: TArgs) => TReturnValue | Promise<TReturnValue>)

		return typeof returnValue === 'function'
			? (returnValue as (...args: TArgs) => TReturnValue | Promise<TReturnValue>)(...args)
			: returnValue as TReturnValue
	}

	const handlers = Object.keys(lookup)
		.map((key) => `"${key}"`)
		.join(', ')

	throw new Error(`Tried to handle "${value}" but there is no handler defined. Only defined handlers are: ${handlers}.`)
}

export function wrap<T>(value: undefined | T | T[]): T[] {
	if (value === undefined) {
		return []
	}

	return Array.isArray(value) ? value : [value]
}

/**
 * Returns the object only if the condition is true. Useful for conditionally merging in an object using the spread operator.
 */
export function mergeObject<T extends object>(condition: any, data: T): T | object {
	if (!condition) {
		return {}
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
