import { parse, stringify } from 'picoquery'

export type QueryArrayFormat = 'indices' | 'brackets'

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

	return parse(source, {
		nesting: true,
		nestingSyntax: 'index',
		arrayRepeat: true,
		arrayRepeatSyntax: 'bracket',
	}) as Record<string, any>
}

export function stringifyQueryString(value: unknown, options: StringifyQueryOptions = {}): string {
	const source = normalizeQueryValue(value)
	const arrayFormat = options.arrayFormat ?? 'brackets'
	const query = stringify(source as Record<string, any>, {
		nesting: true,
		nestingSyntax: 'index',
		arrayRepeat: arrayFormat === 'brackets',
		arrayRepeatSyntax: 'bracket',
	})
	const normalizedQuery = unescapeBracketSyntaxInKeys(query)

	if (!normalizedQuery) {
		return ''
	}

	return options.addQueryPrefix
		? `?${normalizedQuery}`
		: normalizedQuery
}

function unescapeBracketSyntaxInKeys(query: string): string {
	if (!query) {
		return query
	}

	return query
		.split('&')
		.map((entry) => {
			const separator = entry.indexOf('=')

			if (separator < 0) {
				return decodeBracketSyntax(entry)
			}

			const key = entry.slice(0, separator)
			const value = entry.slice(separator)

			return `${decodeBracketSyntax(key)}${value}`
		})
		.join('&')
}

function decodeBracketSyntax(value: string): string {
	return value
		.replace(/%5B/gi, '[')
		.replace(/%5D/gi, ']')
}

function normalizeQueryValue(value: unknown): unknown {
	if (value instanceof Set) {
		return [...value].map((entry) => normalizeQueryValue(entry))
	}

	if (Array.isArray(value)) {
		return value.map((entry) => normalizeQueryValue(entry))
	}

	if (isPlainObject(value)) {
		return Object.entries(value).reduce((result, [key, entry]) => ({
			...result,
			[key]: normalizeQueryValue(entry),
		}), {})
	}

	return value
}

function isPlainObject(value: unknown): value is Record<string, unknown> {
	if (typeof value !== 'object' || value === null) {
		return false
	}

	const prototype = Object.getPrototypeOf(value)
	return prototype === null || prototype === Object.prototype
}
