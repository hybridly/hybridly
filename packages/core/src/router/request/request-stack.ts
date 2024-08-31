import { debug } from '@hybridly/utils'
import { addResponseToQueue } from '../response/response-stack'
import type { PendingHybridRequest } from '../types'
import { sendHybridRequest } from './request'

interface RequestQueue {
	/** The maximum amount of concurrent requests. */
	maxConcurrent: number
	/** Whether requests in the stack can be interrupted by other requests. */
	interruptible: boolean
	/** Current requests. */
	requests: PendingHybridRequest[]
	processing: boolean
}

const queues = {
	sync: {
		maxConcurrent: 1,
		interruptible: true,
		requests: [],
		processing: false,
	} as RequestQueue,
	async: {
		maxConcurrent: Number.POSITIVE_INFINITY,
		interruptible: false,
		requests: [],
		processing: false,
	} as RequestQueue,
}

export function getRequestQueue(request: PendingHybridRequest): RequestQueue {
	return request.options.async
		 ? queues.async
		 : queues.sync
}

export function enqueueRequest(request: PendingHybridRequest) {
	debug.queue('Enqueuing request', request)
	const queue = getRequestQueue(request)
	queue.requests.push(request)
	processRequestQueue(queue)
}

export async function processRequestQueue(queue: RequestQueue) {
	await process(queue)
}

async function process(queue: RequestQueue) {
	const request = queue.requests[0]

	if (!request) {
		debug.queue('End of request queue.')
		return
	}

	debug.queue('Processing request', request)
	const response = await sendHybridRequest(request).catch(() => undefined)

	if (!response) {
		return
	}

	queue.requests.shift()

	addResponseToQueue({
		request,
		response,
	})

	return await process(queue)
}

// ..
export function removeRequestFromQueue(request: PendingHybridRequest) {
	const stack = getRequestQueue(request)
	stack.requests = stack.requests.filter((r) => r !== request)
}

export function interruptInFlight(queue: RequestQueue): void {
	cancelRequest(queue, { interrupted: true, force: false })
}

export function cancelSyncRequest(): void {
	cancelRequest(queues.sync, { cancelled: true, force: true })
}

interface CancelRequestOptions {
	cancelled?: boolean
	interrupted?: boolean
	force: boolean
}

function cancelRequest(queue: RequestQueue, options: CancelRequestOptions): void {
	if (!shouldCancelRequest(queue, options.force)) {
		return
	}

	const request = queue.requests.shift()

	if (request) {
		request.completed = false
		request.cancelled = options.cancelled ?? false
		request.interrupted = options.interrupted ?? false
		request.controller?.abort()
	}
}

function shouldCancelRequest(queue: RequestQueue, force: boolean) {
	if (force) {
		return true
	}

	return queue.interruptible && queue.requests.length >= queue.maxConcurrent
}
