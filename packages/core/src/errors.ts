import { AxiosResponse } from 'axios'

export class NotAHybridlyResponseError extends Error {
	constructor(public response: AxiosResponse) {
		super()
	}
}

export class VisitCancelledError extends Error {}
