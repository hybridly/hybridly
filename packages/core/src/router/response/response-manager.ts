import { debug } from '@hybridly/utils'
import { getInternalRouterContext } from '../../context'
import type { HttpResponse } from '../../http'
import { runHooks } from '../../plugins'
import { rejectOptimisticRequest } from '../optimistic'
import type { PendingHybridRequest } from '../types'
import { handleHybridRequestResponse } from './response'

export interface HybridRequestResponse {
	request: PendingHybridRequest
	response: HttpResponse
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

	try {
		response.request.resolve(await handleHybridRequestResponse(response))
	} catch (error) {
		const resolvedError = error instanceof Error
			? error
			: new Error('Unknown error during response processing.')

		try {
			rejectOptimisticRequest(response.request)
		} catch (rollbackError) {
			console.error(rollbackError)
		}

		console.error(resolvedError)
		response.request.resolve({ error: resolvedError })
	} finally {
		debug.router('Ended navigation.', response.request)
		await runHooks('after', response.request.options.hooks, response.request, getInternalRouterContext())
	}

	return await processNextResponse()
}
