import { debug } from '@hybridly/utils'
import type { AxiosResponse } from 'axios'
import type { PendingHybridRequest } from '../types'
import { handleHybridRequestResponse } from './response'

export interface HybridRequestResponse {
	request: PendingHybridRequest
	response: AxiosResponse
}

const queue: HybridRequestResponse[] = []
let processing = false

/**
 * Adds a response to the queue and starts processing it if not already doing so.
 */
export function enqueueResponse(response: HybridRequestResponse) {
	debug.queue('Enqueuing response', response)
	queue.push(response)
	processResponseQueue()
}

async function processResponseQueue() {
	if (processing) {
		return
	}

	processing = true
	await processNextResponse()
	processing = false
}

async function processNextResponse() {
	const response = queue.shift()

	if (!response) {
		debug.queue('End of response queue.')
		return
	}

	debug.queue('Processing response', response)
	response.request.resolve(await handleHybridRequestResponse(response))

	return await processNextResponse()
}
