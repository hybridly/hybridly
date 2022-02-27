import defu from 'defu'
import { VisitPayload } from 'sleightful'
import type { PartialDeep } from 'type-fest'
import { createContext, RouterContext, RouterContextOptions } from '../src/router/context'

export const noop = () => ({} as any)
export const returnsArgs = (...args: any) => args

export function makeVisitPayload(payload: PartialDeep<VisitPayload> = {}): VisitPayload {
	return defu(payload as VisitPayload, {
		url: 'https://localhost',
		version: 'abc123',
		view: {
			name: 'default.view',
			properties: {},
		},
	})
}

export function makeRouterContextOptions(options: PartialDeep<RouterContextOptions> = {}): RouterContextOptions {
	return defu(options as RouterContextOptions, {
		payload: makeVisitPayload(),
		adapter: {
			resolveComponent: noop,
			swapDialog: noop,
			swapView: noop,
		},
	})
}

export function fakeRouterContext(options: PartialDeep<RouterContextOptions> = {}): RouterContext {
	return createContext(makeRouterContextOptions(options))
}
