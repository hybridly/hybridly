import { beforeEach, test, vi } from 'vitest'
import { HYBRIDLY_HEADER } from '../../src/constants'
import { getInternalRouterContext, getRouterContext } from '../../src/context'
import type { Adapter } from '../../src/context'
import { HttpAbortError, HttpError, type HttpClient, type HttpHeaders, type HttpRequest, type HttpResponse } from '../../src/http'
import type { HybridPayload } from '../../src/router'
import { serializeContext } from '../../src/router/history'
import { performHybridNavigation } from '../../src/router/request/request'
import { fakePayload, fakeRouterContext } from '../utils'

interface DeferredRequest {
	config: HttpRequest
	resolve: (response: HttpResponse<any>) => void
	reject: (error: Error) => void
}

function createDeferredHttpClient() {
	const requests: DeferredRequest[] = []

	const http: HttpClient = {
		request: async <T = unknown>(config: HttpRequest) => {
			return await new Promise<HttpResponse<T>>((resolve, reject) => {
				const request = new XMLHttpRequest()
				const abort = () => reject(new HttpAbortError({
					config,
					request,
					reason: config.signal?.reason,
				}))

				if (config.signal?.aborted) {
					abort()
					return
				}

				config.signal?.addEventListener('abort', abort, { once: true })
				requests.push({ config, resolve, reject })
			})
		},
	}

	return { http, requests }
}

function makeResponse(
	data: HybridPayload | Record<string, unknown> = fakePayload(),
	options: { headers?: false | Record<string, string>; status?: number } = {},
): HttpResponse {
	const rawData = new ArrayBuffer(0)
	const headerEntries = options.headers === false
		? {}
		: {
			[HYBRIDLY_HEADER]: 'true',
			...options.headers,
		}
	const headers = new Headers(headerEntries)

	return {
		data,
		rawData,
		status: options.status ?? 200,
		statusText: 'OK',
		headers: {
			all: headerEntries,
			get: (name) => headers.get(name) ?? undefined,
			has: (name) => headers.has(name),
			isContentType: (matcher) => {
				const contentType = headers.get('content-type') ?? ''

				return typeof matcher === 'string'
					? contentType.includes(matcher)
					: matcher.test(contentType)
			},
		} satisfies HttpHeaders,
		request: new XMLHttpRequest(),
		config: {
			url: 'https://bluebird.test',
		},
		toBlob: (type = 'application/octet-stream') => new Blob([rawData], { type }),
	}
}

async function fakeOptimisticRouter(
	initialProperties: Record<string, any> = {},
	options: { adapter?: Partial<Adapter> } = {},
) {
	const { http, requests } = createDeferredHttpClient()

	await fakeRouterContext({
		http,
		adapter: options.adapter,
		payload: {
			url: 'https://bluebird.test/current',
			view: {
				component: 'users.index',
				properties: initialProperties,
				deferred: {},
				mergeable: [],
			},
		},
	})

	return { requests }
}

beforeEach(() => {
	vi.restoreAllMocks()
})

async function flushNavigationStart() {
	await new Promise((resolve) => setTimeout(resolve))
}

test('applies optimistic properties before the server responds and replaces them on success', async ({ expect }) => {
	const { requests } = await fakeOptimisticRouter({ count: 0 })

	const navigation = performHybridNavigation({
		url: 'https://bluebird.test/users',
		method: 'POST',
		updateImmediately: (properties) => ({
			count: (properties.count as number) + 1,
		}),
	})
	await flushNavigationStart()

	expect(getRouterContext().view.properties.count).toBe(1)

	requests[0].resolve(makeResponse(fakePayload({
		view: {
			component: 'users.index',
			properties: { count: 10 },
			deferred: {},
			mergeable: [],
		},
	})))

	await navigation

	expect(getRouterContext().view.properties.count).toBe(10)
})

test('passes finalized preserved-view properties to the adapter', async ({ expect }) => {
	const swappedProperties: unknown[] = []
	const { requests } = await fakeOptimisticRouter({
		receivedAt: '10:00',
		status: 'clean',
	}, {
		adapter: {
			onViewSwap: async (options) => {
				swappedProperties.push(options.properties)
			},
		},
	})

	const navigation = performHybridNavigation({
		url: 'https://bluebird.test/users',
		method: 'POST',
		updateImmediately: () => ({
			status: 'saving',
		}),
	})
	await flushNavigationStart()

	expect(getRouterContext().view.properties.status).toBe('saving')

	requests[0].resolve(makeResponse({
		...fakePayload(),
		view: {
			component: undefined,
			properties: {
				status: 'saved',
			},
			deferred: {},
			mergeable: [],
		},
	}))

	await navigation

	expect(getRouterContext().view.properties).toEqual({
		receivedAt: '10:00',
		status: 'saved',
	})
	expect(swappedProperties).toEqual([{
		receivedAt: '10:00',
		status: 'saved',
	}])
})

test('passes finalized rollback properties to the adapter for failed preserved-view responses', async ({ expect }) => {
	const swappedProperties: unknown[] = []
	const { requests } = await fakeOptimisticRouter({
		receivedAt: '10:00',
		status: 'clean',
	}, {
		adapter: {
			onViewSwap: async (options) => {
				swappedProperties.push(options.properties)
			},
		},
	})

	const navigation = performHybridNavigation({
		url: 'https://bluebird.test/users',
		method: 'POST',
		updateImmediately: () => ({
			status: 'saving',
		}),
	})
	await flushNavigationStart()

	expect(getRouterContext().view.properties.status).toBe('saving')

	requests[0].resolve(makeResponse({
		...fakePayload(),
		validation: {
			default: {
				status: 'Invalid status.',
			},
		},
		view: {
			component: undefined,
			properties: {
				receivedAt: '10:01',
			},
			deferred: {},
			mergeable: [],
		},
	}))

	await navigation

	expect(getRouterContext().view.properties).toEqual({
		receivedAt: '10:01',
		status: 'clean',
	})
	expect(swappedProperties).toEqual([{
		receivedAt: '10:01',
		status: 'clean',
	}])
})

test('uses optimistic table values as the merge base for touched mergeable properties', async ({ expect }) => {
	const { requests } = await fakeOptimisticRouter({
		notices: {
			records: [
				{ id: 1, name: 'Notice 1' },
				{ id: 2, name: 'Notice 2' },
				{ id: 3, name: 'Notice 3' },
			],
			cells: [
				{ key: 1, columns: { name: { value: 'Notice 1' } } },
				{ key: 2, columns: { name: { value: 'Notice 2' } } },
				{ key: 3, columns: { name: { value: 'Notice 3' } } },
			],
		},
	})

	const navigation = performHybridNavigation({
		url: 'https://bluebird.test/notices/delete',
		method: 'POST',
		updateImmediately: (properties) => {
			const notices = properties.notices as Record<string, any>

			return {
				notices: {
					...notices,
					records: notices.records.filter((record: { id: number }) => record.id !== 2),
					cells: notices.cells.filter((cell: { key: number }) => cell.key !== 2),
				},
			}
		},
	})
	await flushNavigationStart()

	expect(getRouterContext().view.properties.notices).toEqual({
		records: [
			{ id: 1, name: 'Notice 1' },
			{ id: 3, name: 'Notice 3' },
		],
		cells: [
			{ key: 1, columns: { name: { value: 'Notice 1' } } },
			{ key: 3, columns: { name: { value: 'Notice 3' } } },
		],
	})

	requests[0].resolve(makeResponse(fakePayload({
		view: {
			component: 'users.index',
			properties: {
				notices: {
					records: [
						{ id: 1, name: 'Notice 1 from server' },
						{ id: 3, name: 'Notice 3 from server' },
					],
					cells: [
						{ key: 1, columns: { name: { value: 'Notice 1 from server' } } },
						{ key: 3, columns: { name: { value: 'Notice 3 from server' } } },
					],
				},
			},
			deferred: {},
			mergeable: [['notices', false, null, ['records', 'cells'], { records: 'id', cells: 'key' }]],
		},
	})))

	await navigation

	expect(getRouterContext().view.properties.notices).toEqual({
		records: [
			{ id: 1, name: 'Notice 1 from server' },
			{ id: 3, name: 'Notice 3 from server' },
		],
		cells: [
			{ key: 1, columns: { name: { value: 'Notice 1 from server' } } },
			{ key: 3, columns: { name: { value: 'Notice 3 from server' } } },
		],
	})
})

test('does not commit newer optimistic mergeable layers when an older request succeeds', async ({ expect }) => {
	const { requests } = await fakeOptimisticRouter({
		notices: {
			records: [
				{ id: 1, name: 'Notice 1' },
				{ id: 2, name: 'Notice 2' },
				{ id: 3, name: 'Notice 3' },
			],
			cells: [
				{ key: 1, columns: { name: { value: 'Notice 1' } } },
				{ key: 2, columns: { name: { value: 'Notice 2' } } },
				{ key: 3, columns: { name: { value: 'Notice 3' } } },
			],
		},
	})
	vi.spyOn(console, 'error').mockImplementation(() => {})

	const older = performHybridNavigation({
		url: 'https://bluebird.test/notices/delete/1',
		method: 'POST',
		mode: 'async',
		updateImmediately: (properties) => {
			const notices = properties.notices as Record<string, any>

			return {
				notices: {
					...notices,
					records: notices.records.filter((record: { id: number }) => record.id !== 1),
					cells: notices.cells.filter((cell: { key: number }) => cell.key !== 1),
				},
			}
		},
	})
	const newer = performHybridNavigation({
		url: 'https://bluebird.test/notices/delete/2',
		method: 'POST',
		mode: 'async',
		updateImmediately: (properties) => {
			const notices = properties.notices as Record<string, any>

			return {
				notices: {
					...notices,
					records: notices.records.filter((record: { id: number }) => record.id !== 2),
					cells: notices.cells.filter((cell: { key: number }) => cell.key !== 2),
				},
			}
		},
	})
	await flushNavigationStart()

	expect(getRouterContext().view.properties.notices).toEqual({
		records: [
			{ id: 3, name: 'Notice 3' },
		],
		cells: [
			{ key: 3, columns: { name: { value: 'Notice 3' } } },
		],
	})

	requests[0].resolve(makeResponse(fakePayload({
		view: {
			component: 'users.index',
			properties: {
				notices: {
					records: [
						{ id: 2, name: 'Notice 2 from server' },
						{ id: 3, name: 'Notice 3 from server' },
					],
					cells: [
						{ key: 2, columns: { name: { value: 'Notice 2 from server' } } },
						{ key: 3, columns: { name: { value: 'Notice 3 from server' } } },
					],
				},
			},
			deferred: {},
			mergeable: [['notices', false, null, ['records', 'cells'], { records: 'id', cells: 'key' }]],
		},
	})))
	await older

	expect(getRouterContext().view.properties.notices).toEqual({
		records: [
			{ id: 3, name: 'Notice 3 from server' },
		],
		cells: [
			{ key: 3, columns: { name: { value: 'Notice 3 from server' } } },
		],
	})

	requests[1].reject(new HttpError('Network Error', {
		config: requests[1].config,
		request: new XMLHttpRequest(),
	}))
	await newer

	expect(getRouterContext().view.properties.notices).toEqual({
		records: [
			{ id: 2, name: 'Notice 2 from server' },
			{ id: 3, name: 'Notice 3 from server' },
		],
		cells: [
			{ key: 2, columns: { name: { value: 'Notice 2 from server' } } },
			{ key: 3, columns: { name: { value: 'Notice 3 from server' } } },
		],
	})
})

test('supports nested mergeable paths when building an optimistic response base', async ({ expect }) => {
	const { requests } = await fakeOptimisticRouter({
		dashboard: {
			notices: {
				records: [
					{ id: 1, name: 'Notice 1' },
					{ id: 2, name: 'Notice 2' },
				],
				cells: [
					{ key: 1, columns: { name: { value: 'Notice 1' } } },
					{ key: 2, columns: { name: { value: 'Notice 2' } } },
				],
			},
			meta: {
				total: 2,
			},
		},
	})

	const navigation = performHybridNavigation({
		url: 'https://bluebird.test/dashboard/notices/delete',
		method: 'POST',
		updateImmediately: (properties) => {
			const dashboard = properties.dashboard as Record<string, any>

			return {
				dashboard: {
					...dashboard,
					notices: {
						...dashboard.notices,
						records: dashboard.notices.records.filter((record: { id: number }) => record.id !== 1),
						cells: dashboard.notices.cells.filter((cell: { key: number }) => cell.key !== 1),
					},
				},
			}
		},
	})
	await flushNavigationStart()

	requests[0].resolve(makeResponse(fakePayload({
		view: {
			component: 'users.index',
			properties: {
				dashboard: {
					notices: {
						records: [
							{ id: 2, name: 'Notice 2 from server' },
						],
						cells: [
							{ key: 2, columns: { name: { value: 'Notice 2 from server' } } },
						],
					},
					meta: {
						total: 1,
					},
				},
			},
			deferred: {},
			mergeable: [['dashboard.notices', false, null, ['records', 'cells'], { records: 'id', cells: 'key' }]],
		},
	})))

	await navigation

	expect(getRouterContext().view.properties.dashboard).toEqual({
		notices: {
			records: [
				{ id: 2, name: 'Notice 2 from server' },
			],
			cells: [
				{ key: 2, columns: { name: { value: 'Notice 2 from server' } } },
			],
		},
		meta: {
			total: 1,
		},
	})
})

test('shallow-merges returned optimistic properties', async ({ expect }) => {
	const { requests } = await fakeOptimisticRouter({
		profile: {
			name: 'Ada',
			role: 'admin',
		},
	})

	const navigation = performHybridNavigation({
		url: 'https://bluebird.test/users',
		method: 'POST',
		updateImmediately: () => ({
			profile: { name: 'Grace' },
		}),
	})
	await flushNavigationStart()

	expect(getRouterContext().view.properties.profile).toEqual({
		name: 'Grace',
	})

	requests[0].resolve(makeResponse())
	await navigation
})

test('does not snapshot deeply equal returned keys on validation failure', async ({ expect }) => {
	const { requests } = await fakeOptimisticRouter({
		count: 0,
		unchanged: { values: [1, 2] },
	})

	const navigation = performHybridNavigation({
		url: 'https://bluebird.test/users',
		method: 'POST',
		updateImmediately: () => ({
			count: 1,
			unchanged: { values: [1, 2] },
		}),
	})
	await flushNavigationStart()

	expect(getRouterContext().view.properties).toEqual({
		count: 1,
		unchanged: { values: [1, 2] },
	})

	requests[0].resolve(makeResponse(fakePayload({
		validation: {
			default: {
				name: 'Required',
			},
		},
		view: {
			component: 'users.index',
			properties: {
				count: 100,
				unchanged: { values: [3] },
			},
			deferred: {},
			mergeable: [],
		},
	})))

	await navigation

	expect(getRouterContext().view.properties).toEqual({
		count: 0,
		unchanged: { values: [3] },
	})
})

test('rolls back optimistic properties and preserves validation errors', async ({ expect }) => {
	const { requests } = await fakeOptimisticRouter({
		status: 'clean',
	})
	let validationErrors: Record<string, unknown> | undefined

	const navigation = performHybridNavigation({
		url: 'https://bluebird.test/users',
		method: 'POST',
		updateImmediately: () => ({
			status: 'saving',
		}),
		hooks: {
			'validation-error': (errors) => {
				validationErrors = errors
			},
		},
	})
	await flushNavigationStart()

	requests[0].resolve(makeResponse(fakePayload({
		validation: {
			default: {
				name: 'Required',
			},
		},
		view: {
			component: 'users.index',
			properties: {
				status: 'server',
				receivedAt: '10:00',
			},
			deferred: {},
			mergeable: [],
		},
	})))

	await navigation

	expect(getRouterContext().view.properties).toEqual({
		status: 'clean',
		receivedAt: '10:00',
	})
	expect(getRouterContext().validation.default).toEqual({
		name: 'Required',
	})
	expect(validationErrors).toEqual({
		name: 'Required',
	})
})

test('rolls back optimistic properties on invalid responses', async ({ expect }) => {
	const { requests } = await fakeOptimisticRouter({ status: 'clean' })
	vi.spyOn(console, 'warn').mockImplementation(() => {})

	const navigation = performHybridNavigation({
		url: 'https://bluebird.test/users',
		method: 'POST',
		updateImmediately: () => ({
			status: 'saving',
		}),
	})
	await flushNavigationStart()

	requests[0].resolve(makeResponse({ html: '<div>invalid</div>' }, { headers: false }))

	await navigation

	expect(getRouterContext().view.properties.status).toBe('clean')
})

test('rolls back optimistic properties on transport failures', async ({ expect }) => {
	const { requests } = await fakeOptimisticRouter({ status: 'clean' })
	vi.spyOn(console, 'error').mockImplementation(() => {})

	const navigation = performHybridNavigation({
		url: 'https://bluebird.test/users',
		method: 'POST',
		updateImmediately: () => ({
			status: 'saving',
		}),
	})
	await flushNavigationStart()

	requests[0].reject(new HttpError('Network Error', {
		config: requests[0].config,
		request: new XMLHttpRequest(),
	}))

	await navigation

	expect(getRouterContext().view.properties.status).toBe('clean')
})

test('rolls back optimistic properties when response processing throws', async ({ expect }) => {
	const { requests } = await fakeOptimisticRouter({ status: 'clean' })
	vi.spyOn(console, 'error').mockImplementation(() => {})
	let afterCalled = false

	const navigation = performHybridNavigation({
		url: 'https://bluebird.test/users',
		method: 'POST',
		updateImmediately: () => ({
			status: 'saving',
		}),
		hooks: {
			data: () => {
				throw new Error('Response hook failed.')
			},
			after: () => {
				afterCalled = true
			},
		},
	})
	await flushNavigationStart()

	expect(getRouterContext().view.properties.status).toBe('saving')

	requests[0].resolve(makeResponse())

	const response = await navigation

	expect(response.error?.message).toBe('Response hook failed.')
	expect(getRouterContext().view.properties.status).toBe('clean')
	expect(afterCalled).toBe(true)
})

test('does not carry optimistic properties into a different response component', async ({ expect }) => {
	const { requests } = await fakeOptimisticRouter({ status: 'clean' })

	const navigation = performHybridNavigation({
		url: 'https://bluebird.test/users/1',
		method: 'POST',
		updateImmediately: () => ({
			status: 'saving',
		}),
	})
	await flushNavigationStart()

	requests[0].resolve(makeResponse(fakePayload({
		view: {
			component: 'users.show',
			properties: {
				user: { id: 1 },
			},
			deferred: {},
			mergeable: [],
		},
	})))

	await navigation

	expect(getRouterContext().view.component).toBe('users.show')
	expect(getRouterContext().view.properties).toEqual({
		user: { id: 1 },
	})
})

test('rolls back interrupted optimistic updates before applying the next request', async ({ expect }) => {
	const { requests } = await fakeOptimisticRouter({ status: 'clean' })

	const interrupted = performHybridNavigation({
		url: 'https://bluebird.test/users/1',
		method: 'POST',
		updateImmediately: () => ({
			status: 'first',
		}),
	})
	await flushNavigationStart()

	expect(getRouterContext().view.properties.status).toBe('first')

	const navigation = performHybridNavigation({
		url: 'https://bluebird.test/users/2',
		method: 'POST',
		updateImmediately: () => ({
			status: 'second',
		}),
	})
	await flushNavigationStart()

	expect(getRouterContext().view.properties.status).toBe('second')

	requests[1].resolve(makeResponse(fakePayload({
		view: {
			component: 'users.index',
			properties: { status: 'server' },
			deferred: {},
			mergeable: [],
		},
	})))

	await navigation
	await interrupted

	expect(getRouterContext().view.properties.status).toBe('server')
})

test('preserves newer overlapping optimistic layers when an older request rolls back', async ({ expect }) => {
	const { requests } = await fakeOptimisticRouter({ count: 0 })
	vi.spyOn(console, 'error').mockImplementation(() => {})

	const older = performHybridNavigation({
		url: 'https://bluebird.test/users/1',
		method: 'POST',
		mode: 'async',
		updateImmediately: () => ({
			count: 1,
		}),
	})
	const newer = performHybridNavigation({
		url: 'https://bluebird.test/users/2',
		method: 'POST',
		mode: 'async',
		updateImmediately: (properties) => ({
			count: (properties.count as number) + 1,
		}),
	})
	await flushNavigationStart()

	expect(getRouterContext().view.properties.count).toBe(2)

	requests[0].reject(new HttpError('Network Error', {
		config: requests[0].config,
		request: new XMLHttpRequest(),
	}))
	await older

	expect(getRouterContext().view.properties.count).toBe(1)

	requests[1].resolve(makeResponse(fakePayload({
		view: {
			component: 'users.index',
			properties: { count: 20 },
			deferred: {},
			mergeable: [],
		},
	})))

	await newer

	expect(getRouterContext().view.properties.count).toBe(20)
})

test('rolls back a newer optimistic layer to the latest committed server value', async ({ expect }) => {
	const { requests } = await fakeOptimisticRouter({ count: 0 })

	expect(getRouterContext().view.properties.count).toBe(0)

	const older = performHybridNavigation({
		url: 'https://bluebird.test/users/1',
		method: 'POST',
		mode: 'async',
		updateImmediately: () => ({
			count: 1,
		}),
	})
	const newer = performHybridNavigation({
		url: 'https://bluebird.test/users/2',
		method: 'POST',
		mode: 'async',
		updateImmediately: (properties) => ({
			count: (properties.count as number) + 1,
		}),
	})
	await flushNavigationStart()

	expect(requests).toHaveLength(2)

	requests[0].resolve(makeResponse(fakePayload({
		view: {
			component: 'users.index',
			properties: { count: 10 },
			deferred: {},
			mergeable: [],
		},
	})))
	await flushNavigationStart()
	await older

	expect(getRouterContext().view.properties.count).toBe(11)

	requests[1].resolve(makeResponse(fakePayload({
		validation: {
			default: {
				count: 'Invalid count.',
			},
		},
		view: {
			component: 'users.index',
			properties: { count: 999 },
			deferred: {},
			mergeable: [],
		},
	})))
	await newer

	expect(getRouterContext().view.properties.count).toBe(10)
})

test('keeps re-rendering older optimistic layers when a newer request succeeds first', async ({ expect }) => {
	const { requests } = await fakeOptimisticRouter({
		notices: {
			records: [
				{ id: 1, name: 'Notice 1' },
				{ id: 2, name: 'Notice 2' },
				{ id: 3, name: 'Notice 3' },
			],
		},
	})

	const older = performHybridNavigation({
		url: 'https://bluebird.test/notices/delete/1',
		method: 'POST',
		mode: 'async',
		updateImmediately: (properties) => {
			const notices = properties.notices as Record<string, any>

			return {
				notices: {
					...notices,
					records: notices.records.filter((record: { id: number }) => record.id !== 1),
				},
			}
		},
	})
	const newer = performHybridNavigation({
		url: 'https://bluebird.test/notices/delete/2',
		method: 'POST',
		mode: 'async',
		updateImmediately: (properties) => {
			const notices = properties.notices as Record<string, any>

			return {
				notices: {
					...notices,
					records: notices.records.filter((record: { id: number }) => record.id !== 2),
				},
			}
		},
	})
	await flushNavigationStart()

	requests[1].resolve(makeResponse(fakePayload({
		view: {
			component: 'users.index',
			properties: {
				notices: {
					records: [
						{ id: 1, name: 'Notice 1 from server' },
						{ id: 3, name: 'Notice 3 from server' },
					],
				},
			},
			deferred: {},
			mergeable: [['notices', false, 'id', []]],
		},
	})))
	await flushNavigationStart()
	await newer

	expect(getRouterContext().view.properties.notices).toEqual({
		records: [
			{ id: 3, name: 'Notice 3 from server' },
		],
	})

	requests[0].resolve(makeResponse(fakePayload({
		view: {
			component: 'users.index',
			properties: {
				notices: {
					records: [
						{ id: 3, name: 'Notice 3 from final server' },
					],
				},
			},
			deferred: {},
			mergeable: [['notices', false, 'id', []]],
		},
	})))
	await flushNavigationStart()
	await older

	expect(getRouterContext().view.properties.notices).toEqual({
		records: [
			{ id: 3, name: 'Notice 3 from final server' },
		],
	})
})

test('settles independent optimistic layers without affecting each other', async ({ expect }) => {
	const { requests } = await fakeOptimisticRouter({ count: 0, status: 'clean' })
	vi.spyOn(console, 'error').mockImplementation(() => {})

	const count = performHybridNavigation({
		url: 'https://bluebird.test/count',
		method: 'POST',
		mode: 'async',
		updateImmediately: () => ({
			count: 1,
		}),
	})
	const status = performHybridNavigation({
		url: 'https://bluebird.test/status',
		method: 'POST',
		mode: 'async',
		updateImmediately: () => ({
			status: 'saving',
		}),
	})
	await flushNavigationStart()

	requests[0].reject(new HttpError('Network Error', {
		config: requests[0].config,
		request: new XMLHttpRequest(),
	}))
	await count

	expect(getRouterContext().view.properties).toEqual({
		count: 0,
		status: 'saving',
	})

	requests[1].resolve(makeResponse(fakePayload({
		view: {
			component: 'users.index',
			properties: { count: 0, status: 'saved' },
			deferred: {},
			mergeable: [],
		},
	})))
	await status

	expect(getRouterContext().view.properties).toEqual({
		count: 0,
		status: 'saved',
	})
})

test('serializes committed properties instead of rendered optimistic properties', async ({ expect }) => {
	const { requests } = await fakeOptimisticRouter({ count: 0 })

	const navigation = performHybridNavigation({
		url: 'https://bluebird.test/users',
		method: 'POST',
		updateImmediately: () => ({
			count: 1,
		}),
	})
	await flushNavigationStart()

	const serialized = getRouterContext().serializer.unserialize<any>(
		serializeContext(getInternalRouterContext()),
	)

	expect(getRouterContext().view.properties.count).toBe(1)
	expect(serialized.view.properties.count).toBe(0)

	requests[0].resolve(makeResponse())
	await navigation
})

test('does not expose optimistic bookkeeping on the public context', async ({ expect }) => {
	const { requests } = await fakeOptimisticRouter({ count: 0 })

	const navigation = performHybridNavigation({
		url: 'https://bluebird.test/users',
		method: 'POST',
		updateImmediately: () => ({
			count: 1,
		}),
	})
	await flushNavigationStart()

	expect(getRouterContext().view.properties.count).toBe(1)
	expect(Object.hasOwn(getRouterContext(), 'propertyState')).toBe(false)

	requests[0].resolve(makeResponse())
	await navigation
})
