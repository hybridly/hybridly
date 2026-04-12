import { ConditionalNavigationOption, HybridRequestOptions } from './router'

export function createPromiseWithResolvers<T>(): PromiseWithResolvers<T> {
	let resolve: any
	let reject: any
	const promise = new Promise<T>((_resolve, _reject) => {
		resolve = _resolve
		reject = _reject
	})
	return { promise, resolve, reject }
}

export function evaluateConditionalOption<T extends boolean | string>(options: HybridRequestOptions, option?: ConditionalNavigationOption<T>) {
	return typeof option === 'function'
		? option(options)
		: option
}
