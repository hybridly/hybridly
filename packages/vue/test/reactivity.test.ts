import { constants, router as hybridlyRouter, type HttpClient, type HttpRequest, type HttpResponse } from '@hybridly/core'
import { useForm, useProperty } from '@hybridly/vue'
import { mount } from '@vue/test-utils'
import { test, vi } from 'vitest'
import { defineComponent, nextTick, watch } from 'vue'
import type { HttpHeaders } from '../../core/src/http'
import { server } from '../../core/test/server'
import { fakePayload, fakeRouter, mockSuccessfulUrl } from '../../core/test/utils'
import { state } from '../../vue/src/stores/state'

interface DeferredRequest {
	config: HttpRequest
	resolve: (response: HttpResponse<any>) => void
}

function createDeferredHttpClient() {
	const requests: DeferredRequest[] = []

	const http: HttpClient = {
		request: async <T = unknown>(config: HttpRequest) => {
			return await new Promise<HttpResponse<T>>((resolve) => {
				requests.push({ config, resolve })
			})
		},
	}

	return { http, requests }
}

function makeResponse(data = fakePayload()): HttpResponse {
	const rawData = new ArrayBuffer(0)
	const headerEntries = { [constants.HYBRIDLY_HEADER]: 'true' }
	const headers = new Headers(headerEntries)

	return {
		data,
		rawData,
		status: 200,
		statusText: 'OK',
		headers: {
			all: headerEntries,
			get: (name: string) => headers.get(name) ?? undefined,
			has: (name: string) => headers.has(name),
			isContentType: (matcher: string | RegExp) => {
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

async function flushNavigationStart() {
	await new Promise((resolve) => setTimeout(resolve))
}

test('it has no reactivity issues', async ({ expect }) => {
	type TestProperty = Array<{
		deep: {
			data1?: boolean
			data2: boolean
		}
	}>

	type ExposedComponent = {
		testProperty: TestProperty
		form: {
			submit: () => Promise<unknown>
		}
	}

	const initialTestProperty: TestProperty = [
		{
			deep: {
				data2: true,
			},
		},
		{
			deep: {
				data2: true,
			},
		},
	]

	const testData = {
		with: initialTestProperty,
	}

	const router = await fakeRouter({
		payload: {
			view: {
				properties: {
					test: testData,
				},
			},
		},
		adapter: {
			// TODO we need a better way to mock the adapter than to pick the logic from initialize.ts
			onContextUpdate: (context) => {
				state.setContext(context)
			},

			onViewSwap: async (options) => {
				state.setProperties(options.properties)
			},
		},
	})
	state.setContext(router)
	state.setProperties({
		test: testData,
	})

	const watchFn = vi.fn()
	const TestReactivityInComponent = defineComponent({
		setup(_, { expose }) {
			const testProperty = useProperty<TestProperty>('test.with')

			watch(testProperty, watchFn)

			const form = useForm({
				url: 'http://localhost.test/navigation',
				method: 'POST',
				only: ['test'],
				fields: {},
			})

			expose({
				testProperty,
				form,
			})
		},
		template: '<div></div>',
	})
	const wrapper = mount(TestReactivityInComponent)
	const vm = wrapper.vm as unknown as ExposedComponent

	server.resetHandlers(mockSuccessfulUrl('http://localhost.test/navigation', 'post', {}, {
		view: {
			component: 'default.view2',
			properties: {
				test: {
					with: [
						{
							deep: {
								data1: true,
								data2: true,
							},
						},
						{
							deep: {
								data1: false,
								data2: true,
							},
						},
					],
				},
			},
		},
	}))

	expect(vm.testProperty).toMatchObject(testData.with)
	await vm.form.submit()

	expect(testData.with).not.toMatchObject(vm.testProperty)
	expect(vm.testProperty[0].deep.data1).toBeTruthy()

	await nextTick()
	expect(watchFn).toBeCalledTimes(1)
})

test('useProperty reacts to optimistic updates, server replacements, and rollbacks', async ({ expect }) => {
	const { http, requests } = createDeferredHttpClient()
	const context = await fakeRouter({
		http,
		payload: {
			url: 'https://bluebird.test/current',
			view: {
				component: 'default.view',
				properties: {
					status: 'clean',
				},
				deferred: {},
				mergeable: [],
			},
		},
		adapter: {
			onContextUpdate: (context) => {
				state.setContext(context)
			},
			onPropertiesUpdate: (properties) => {
				state.setProperties(properties)
			},
			onViewSwap: async (options) => {
				state.setProperties(options.properties)
			},
		},
	})
	state.setContext(context)
	state.setProperties(context.view.properties)

	const TestOptimisticUpdates = defineComponent({
		setup(_, { expose }) {
			const status = useProperty<string>('status')
			const submit = (status: string) => hybridlyRouter.post('https://bluebird.test/users', {
				updateImmediately: () => ({ status }),
			})

			expose({ status, submit })
		},
		template: '<div></div>',
	})
	const wrapper = mount(TestOptimisticUpdates)
	const vm = wrapper.vm as unknown as {
		status: string
		submit: (status: string) => Promise<unknown>
	}

	const success = vm.submit('saving')
	await flushNavigationStart()
	expect(vm.status).toBe('saving')

	requests[0].resolve(makeResponse(fakePayload({
		view: {
			component: 'default.view',
			properties: {
				status: 'saved',
			},
			deferred: {},
			mergeable: [],
		},
	})))

	await success
	await nextTick()
	expect(vm.status).toBe('saved')

	const failure = vm.submit('deleting')
	await flushNavigationStart()
	expect(vm.status).toBe('deleting')

	requests[1].resolve(makeResponse(fakePayload({
		validation: {
			default: {
				status: 'Cannot delete.',
			},
		},
		view: {
			component: 'default.view',
			properties: {
				status: 'server-error-state',
			},
			deferred: {},
			mergeable: [],
		},
	})))

	await failure
	await nextTick()
	expect(vm.status).toBe('saved')
})
