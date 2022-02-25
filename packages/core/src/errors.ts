import { AxiosResponse } from 'axios'

export class NotASleightfulResponseError extends Error {
	constructor(public response: AxiosResponse) {
		super()
	}
}
