import defu from 'defu'
import type { PartialDeep } from 'type-fest'
import type { HttpResponseInit } from 'msw'
import { HttpResponse } from 'msw'
import type { RouterContext, RouterContextOptions } from '../src/context'
import { HYBRIDLY_HEADER } from '../src/constants'
import { initializeContext } from '../src/context'
import type { HybridPayload } from '../src/router'
import { http } from './server'

export const noop = () => ({} as any)
export const returnsArgs = (...args: any) => args

export function fakePayload(payload: PartialDeep<HybridPayload> = {}): HybridPayload {
	return defu(payload as HybridPayload, {
		url: 'https://localhost',
		version: 'abc123',
		view: {
			component: 'default.view',
			properties: {},
		},
	})
}

export function makeRouterContextOptions(options: PartialDeep<RouterContextOptions> = {}): RouterContextOptions {
	return defu(options as RouterContextOptions, {
		payload: fakePayload(),
		adapter: {
			resolveComponent: noop,
			swapDialog: noop,
			swapView: noop,
		},
	})
}

export async function fakeRouterContext(options: PartialDeep<RouterContextOptions> = {}): Promise<RouterContext> {
	return await initializeContext(makeRouterContextOptions(options))
}

/** Mocks a request using MSW. */
export function mockUrl(url: string, options: Partial<MockOptions> = {}) {
	const resolved: MockOptions = defu(options, {
		status: 200,
		headers: { [HYBRIDLY_HEADER]: 'true' },
		json: fakePayload(),
	})

	http.get(url, () => {
		const init: HttpResponseInit = {
			status: resolved.status,
			headers: resolved.headers !== false
				? resolved.headers
				: [],
		}

		if (resolved.body) {
			return new HttpResponse(resolved.body, init)
		}

		return HttpResponse.json(resolved.json, init)
	})
}

interface MockOptions {
	status: number
	headers: false | Record<string, any>
	json?: any
	body?: any
}
