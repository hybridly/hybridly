import type { AxiosResponse } from 'axios'

export class NotAHybridResponseError extends Error {
	constructor(public response: AxiosResponse) {
		super()
	}
}

export class NavigationCancelledError extends Error {}
