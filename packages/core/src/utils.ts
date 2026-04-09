export function createPromiseWithResolvers<T>(): PromiseWithResolvers<T> {
	let resolve: any
	let reject: any
	const promise = new Promise<T>((_resolve, _reject) => {
		resolve = _resolve
		reject = _reject
	})
	return { promise, resolve, reject }
}
