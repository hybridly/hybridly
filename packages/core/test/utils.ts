import type { PartialDeep } from 'type-fest'
import { RouterOptions, Visit } from '../src/types'

export const returnsArgs = (...args: any) => args
export function fakeVisit(visit: PartialDeep<Visit> = {}): Visit {
	return {
		view: {
			name: 'my.component',
			properties: visit.view?.properties ?? {},
			url: 'http://localhost',
			...visit.view,
		},
		type: 'page',
		context: '',
		version: '',
		...visit as any,
	}
}

export function fakeRouterOptions(options: PartialDeep<RouterOptions> = {}): RouterOptions {
	return {
		resolve: async() => {},
		...options,
		visit: fakeVisit(options.visit),
		swap: {
			dialog: async() => {},
			view: async() => {},
			...options.swap,
		},
	}
}
