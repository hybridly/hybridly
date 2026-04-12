import type { RequestData } from '@hybridly/utils'
import { QueryValue } from '../query'
import { makeUrl } from '../url'

export interface HttpUploadProgressEvent {
	loaded: number
	total?: number
	lengthComputable: boolean
	percentage: number
}

export interface HttpHeaders {
	all: Readonly<Record<string, string>>
	get: (name: string) => string | undefined
	has: (name: string) => boolean
	isContentType: (matcher: string | RegExp) => boolean
}

export interface HttpRequest {
	url: string
	method?: string
	headers?: Record<string, string>
	params?: RequestData
	data?: unknown
	signal?: AbortSignal
	onUploadProgress?: (event: HttpUploadProgressEvent) => void | Promise<void>
}

export interface HttpResponse<T = unknown> {
	data: T
	rawData: ArrayBuffer
	status: number
	statusText: string
	headers: HttpHeaders
	request: XMLHttpRequest
	config: HttpRequest
	toBlob: (type?: string) => Blob
}

export interface HttpClient {
	request: <T = unknown>(config: HttpRequest) => Promise<HttpResponse<T>>
}

export type HttpErrorCode = 'ERR_ABORTED' | 'ERR_NETWORK' | 'ECONNABORTED'
export type HttpErrorKind = 'abort' | 'network' | 'timeout'

export class HttpError extends Error {
	public readonly config: HttpRequest
	public readonly request: XMLHttpRequest
	public readonly response?: HttpResponse
	public readonly code: HttpErrorCode
	public readonly kind: HttpErrorKind
	public readonly reason?: unknown
	public readonly isHttpError = true

	constructor(message: string, options: HttpErrorOptions) {
		super(message)
		this.name = 'HttpError'
		this.config = options.config
		this.request = options.request
		this.response = options.response
		this.code = options.code ?? 'ERR_NETWORK'
		this.kind = options.kind ?? 'network'
		this.reason = options.reason
	}
}

export class HttpAbortError extends HttpError {
	constructor(options: HttpErrorOptions) {
		super('The request was aborted.', {
			...options,
			code: options.code ?? 'ERR_ABORTED',
			kind: options.kind ?? 'abort',
		})

		this.name = 'AbortError'
	}
}

export function isHttpError(error: unknown): error is HttpError {
	return error instanceof Error && (error as Partial<HttpError>).isHttpError === true
}

export function isHttpAbortError(error: unknown): error is HttpAbortError {
	return isHttpError(error) && error.kind === 'abort'
}

export function createXhrHttpClient(): HttpClient {
	return {
		request: async (config) => await requestWithXhr(config),
	}
}

function requestWithXhr<T>(config: HttpRequest): Promise<HttpResponse<T>> {
	return new Promise<HttpResponse<T>>((resolve, reject) => {
		let settled = false

		const resolveOnce = (response: HttpResponse<T>) => {
			if (settled) {
				return
			}

			settled = true
			cleanupAbort?.()
			resolve(response)
		}

		const rejectOnce = (error: HttpError) => {
			if (settled) {
				return
			}

			settled = true
			cleanupAbort?.()
			reject(error)
		}

		const xhr = new XMLHttpRequest()
		const method = config.method ?? 'GET'
		const url = buildUrl(config.url, config.params)
		const headers = normalizeHeaders(config.headers)
		const body = toRequestBody(config.data, headers)
		const cleanupAbort = registerAbortSignal(config.signal, xhr, (reason) => {
			rejectOnce(
				new HttpAbortError({
					config,
					request: xhr,
					reason,
				}),
			)
		})

		if (config.signal?.aborted) {
			rejectOnce(
				new HttpAbortError({
					config,
					request: xhr,
					reason: config.signal.reason,
				}),
			)
			return
		}

		xhr.open(method, url, true)
		xhr.responseType = 'arraybuffer'

		for (const [key, value] of Object.entries(headers)) {
			xhr.setRequestHeader(key, value)
		}

		if (config.onUploadProgress) {
			xhr.upload.onprogress = (event) => {
				const total = event.lengthComputable ? event.total : undefined
				const percentage = total && total > 0
					? Math.round(event.loaded / total * 100)
					: 0

				void config.onUploadProgress?.({
					loaded: event.loaded,
					total,
					lengthComputable: event.lengthComputable,
					percentage,
				})
			}
		}

		xhr.onload = () => {
			const response = toResponse<T>(xhr, config)
			resolveOnce(response)
		}

		xhr.onerror = () => {
			rejectOnce(
				new HttpError('Network Error', {
					config,
					request: xhr,
					code: 'ERR_NETWORK',
					kind: 'network',
				}),
			)
		}

		xhr.onabort = () => {
			rejectOnce(
				new HttpAbortError({
					config,
					request: xhr,
					reason: config.signal?.reason,
				}),
			)
		}

		xhr.ontimeout = () => {
			rejectOnce(
				new HttpError('The request timed out.', {
					config,
					request: xhr,
					code: 'ECONNABORTED',
					kind: 'timeout',
				}),
			)
		}

		try {
			xhr.send(body as Document | XMLHttpRequestBodyInit | null)
		} catch (error: unknown) {
			rejectOnce(
				new HttpError('Network Error', {
					config,
					request: xhr,
					code: 'ERR_NETWORK',
					kind: 'network',
					reason: error,
				}),
			)
		}
	})
}

function registerAbortSignal(signal: AbortSignal | undefined, xhr: XMLHttpRequest, onAbort: (reason?: unknown) => void): (() => void) | undefined {
	if (!signal) {
		return
	}

	if (signal.aborted) {
		onAbort(signal.reason)
		return
	}

	const abort = () => {
		onAbort(signal.reason)
		xhr.abort()
	}

	signal.addEventListener('abort', abort, { once: true })

	return () => signal.removeEventListener('abort', abort)
}

function buildUrl(url: string, params?: RequestData): string {
	if (!params || Object.keys(params).length === 0) {
		return url
	}

	return makeUrl(url, {
		query: params as Record<string, QueryValue>,
	}).toString()
}

function normalizeHeaders(headers: HttpRequest['headers']): Record<string, string> {
	if (!headers) {
		return {}
	}

	return Object.entries(headers).reduce((result, [key, value]) => ({
		...result,
		[key]: String(value),
	}), {})
}

function toRequestBody(data: unknown, headers: Record<string, string>) {
	if (data === undefined || data === null) {
		return null
	}

	if (typeof FormData !== 'undefined' && data instanceof FormData) {
		return data
	}

	if (typeof data === 'string' || data instanceof Blob || data instanceof URLSearchParams || data instanceof ArrayBuffer || ArrayBuffer.isView(data)) {
		return data
	}

	if (typeof data === 'object') {
		if (!hasHeader(headers, 'content-type')) {
			headers['Content-Type'] = 'application/json'
		}

		return JSON.stringify(data)
	}

	return String(data)
}

function hasHeader(headers: Record<string, string>, headerName: string): boolean {
	const expected = headerName.toLowerCase()

	return Object.keys(headers).some((header) => header.toLowerCase() === expected)
}

function toResponse<T>(xhr: XMLHttpRequest, config: HttpRequest): HttpResponse<T> {
	const headers = createHttpHeaders(parseResponseHeaders(xhr.getAllResponseHeaders()))
	const rawData = toArrayBuffer(xhr.response)
	const responseData = decodeResponseBody(rawData, headers)

	return {
		data: responseData as T,
		rawData,
		status: xhr.status,
		statusText: xhr.statusText,
		headers,
		request: xhr,
		config,
		toBlob: (type) => {
			const resolvedType = type ?? headers.get('content-type')
			return new Blob([rawData], {
				...resolvedType
					? { type: resolvedType }
					: {},
			})
		},
	}
}

function createHttpHeaders(values: Record<string, string>): HttpHeaders {
	const all = Object.freeze({ ...values })

	const get = (name: string) => all[name.toLowerCase()]
	const has = (name: string) => get(name) !== undefined
	const isContentType = (matcher: string | RegExp) => {
		const contentType = get('content-type')

		if (!contentType) {
			return false
		}

		if (typeof matcher === 'string') {
			return contentType.toLowerCase().includes(matcher.toLowerCase())
		}

		return matcher.test(contentType)
	}

	return {
		all,
		get,
		has,
		isContentType,
	}
}

function toArrayBuffer(data: unknown): ArrayBuffer {
	if (data instanceof ArrayBuffer) {
		return data
	}

	if (ArrayBuffer.isView(data)) {
		const bytes = new Uint8Array(data.buffer, data.byteOffset, data.byteLength)
		return bytes.slice().buffer
	}

	if (typeof data === 'string') {
		return new TextEncoder().encode(data).buffer
	}

	return new ArrayBuffer(0)
}

function decodeResponseBody(rawData: ArrayBuffer, headers: HttpHeaders): unknown {
	if (headers.isContentType(/(^|\b|\+)json(\b|;|$)/i)) {
		const text = decodeText(rawData, headers)

		try {
			return JSON.parse(text)
		} catch {
			return text
		}
	}

	if (headers.isContentType(/^text\//i) || headers.isContentType(/xml|javascript|x-www-form-urlencoded/i)) {
		return decodeText(rawData, headers)
	}

	return rawData
}

function decodeText(rawData: ArrayBuffer, headers: HttpHeaders): string {
	const charset = headers.get('content-type')?.match(/charset=([^;]+)/i)?.[1]?.trim()

	try {
		return new TextDecoder(charset).decode(rawData)
	} catch {
		return new TextDecoder().decode(rawData)
	}
}

function parseResponseHeaders(rawHeaders: string): Record<string, string> {
	const lines = rawHeaders.split('\r\n').filter(Boolean)
	const headers: Record<string, string> = {}

	for (const line of lines) {
		const separator = line.indexOf(':')

		if (separator < 0) {
			continue
		}

		const name = line.slice(0, separator).trim().toLowerCase()
		const value = line.slice(separator + 1).trim()

		headers[name] = name in headers
			? `${headers[name]}, ${value}`
			: value
	}

	return headers
}

interface HttpErrorOptions {
	config: HttpRequest
	request: XMLHttpRequest
	response?: HttpResponse
	code?: HttpErrorCode
	kind?: HttpErrorKind
	reason?: unknown
}
