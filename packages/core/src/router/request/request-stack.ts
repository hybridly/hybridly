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

function getRequestQueue(request: PendingHybridRequest): RequestQueue {
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
	if (queue.processing) {
		return
	}

	queue.processing = true
	await process(queue)
	queue.processing = false
}

async function process(queue: RequestQueue) {
	const request = queue.requests.shift()

	if (!request) {
		debug.queue('End of request queue.')
		return
	}

	debug.queue('Processing request', request)
	const response = await sendHybridRequest(request)

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

export function interruptInFlight(request: PendingHybridRequest): void {
	cancelRequest(request, { interrupted: true, force: false })
}

export function cancelInFlight(request: PendingHybridRequest): void {
	cancelRequest(request, { cancelled: true, force: true })
}

interface CancelRequestOptions {
	cancelled?: boolean
	interrupted?: boolean
	force: boolean
}

function cancelRequest(request: PendingHybridRequest, options: CancelRequestOptions): void {
	if (!shouldCancelRequest(request, options.force)) {
		return
	}

	removeRequestFromQueue(request)
	request.completed = false
	request.cancelled = options.cancelled ?? false
	request.interrupted = options.interrupted ?? false
	request.controller?.abort()
}

function shouldCancelRequest(request: PendingHybridRequest, force: boolean) {
	if (force) {
		return true
	}

	const stack = getRequestQueue(request)
	return stack.interruptible && stack.requests.length >= stack.maxConcurrent
}
