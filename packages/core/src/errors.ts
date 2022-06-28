import { AxiosResponse } from 'axios'

export class NotAMonolikitResponseError extends Error {
	constructor(public response: AxiosResponse) {
		super()
	}
}

export class VisitCancelledError extends Error {}
