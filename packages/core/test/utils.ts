import defu from 'defu'
import type { PartialDeep } from 'type-fest'
import { createContext, RouterContext, RouterContextOptions } from '../src/router/context'

export const noop = () => ({} as any)
export const returnsArgs = (...args: any) => args

export function makeRouterContextOptions(options: PartialDeep<RouterContextOptions> = {}): RouterContextOptions {
	return defu({
		payload: {
			url: 'https://localhost',
			version: 'abc123',
			view: {
				name: 'default.view',
				properties: {},
			},
		},
		adapter: {
			resolveComponent: noop,
			swapDialog: noop,
			swapView: noop,
		},
	}, options)
}

export function fakeRouterContext(options: PartialDeep<RouterContextOptions> = {}): RouterContext {
	return createContext(makeRouterContextOptions(options))
}
