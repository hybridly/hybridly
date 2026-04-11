import { isPlainObject } from 'es-toolkit/predicate'
import { parse, stringify } from 'neoqs'

export type QueryArrayFormat = 'indices' | 'brackets'
export type QueryValue =
	| string
	| number
	| boolean
	| null
	| undefined
	| QueryValue[]
	| Set<QueryValue>
	| { [key: string]: QueryValue }

export interface StringifyQueryOptions {
	arrayFormat?: QueryArrayFormat
	addQueryPrefix?: boolean
}

export function parseQueryString(query: string): Record<string, any> {
	const source = query.startsWith('?')
		? query.slice(1)
		: query

	if (!source) {
		return {}
	}

	return parse(source) as Record<string, any>
}

export function stringifyQueryString(value: QueryValue, options: StringifyQueryOptions = {}): string {
	const source = normalizeQueryValue(value)
	const arrayFormat = options.arrayFormat ?? 'brackets'

	const query = stringify(source as Record<string, unknown>, {
		arrayFormat,
		encodeValuesOnly: true,
	})

	if (!query) {
		return ''
	}

	return options.addQueryPrefix
		? `?${query}`
		: query
}

function normalizeQueryValue(value: QueryValue): QueryValue {
	if (value instanceof Set) {
		return [...value].map((entry) => normalizeQueryValue(entry))
	}

	if (Array.isArray(value)) {
		return value.map((entry) => normalizeQueryValue(entry))
	}

	if (isPlainObject(value)) {
		return Object.entries(value).reduce((result, [key, entry]) => ({
			...result,
			[key]: normalizeQueryValue(entry as QueryValue),
		}), {} as { [key: string]: QueryValue })
	}

	return value
}
