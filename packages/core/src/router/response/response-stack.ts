import type { AxiosResponse } from 'axios'
import { debug } from '@hybridly/utils'
import type { PendingHybridRequest } from '../types'
import { handleHybridRequestResponse } from './response'

export interface HybridRequestResponse {
	request: PendingHybridRequest
	response: AxiosResponse
}

const queue: HybridRequestResponse[] = []
let processing = false

export function addResponseToQueue(response: HybridRequestResponse) {
	debug.queue('Enqueuing response', response)
	queue.push(response)
	processResponseQueue()
}

export async function processResponseQueue() {
	if (processing) {
		return
	}

	processing = true
	await process()
	processing = false
}

async function process() {
	const response = queue.shift()

	if (!response) {
		debug.queue('End of response queue.')
		return
	}

	debug.queue('Processing response', response)
	response.request.resolve(await handleHybridRequestResponse(response))

	return await process()
}
