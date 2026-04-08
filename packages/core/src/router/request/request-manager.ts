import { debug } from '@hybridly/utils'
import { enqueueResponse } from '../response/response-manager'
import type { PendingHybridRequest } from '../types'
import { sendHybridRequest } from './request'

interface RequestQueue {
	/** The maximum amount of concurrent requests. */
	concurrencyLimit: number
	/** Whether requests in the queue can be interrupted by other requests. */
	interruptible: boolean
	/** Current requests. */
	requests: PendingHybridRequest[]
	/** Whether this queue is currently being consumed. */
	processing: boolean
}

const queues = {
	/** Navigations cancel each other. */
	navigation: { concurrencyLimit: 1, interruptible: true, requests: [], processing: false } satisfies RequestQueue,
	/** Asynchronous requests (e.g. partial requests) can run concurrently. */
	async: { concurrencyLimit: Number.POSITIVE_INFINITY, interruptible: false, requests: [], processing: false } satisfies RequestQueue,
}

/**
 * Adds a request to the queue and starts processing it if not already doing so.
 */
export function enqueueRequest(request: PendingHybridRequest) {
	debug.queue('Enqueuing request', request)
	const queue = getRequestQueue(request)
	queue.requests.push(request)
	void processRequestQueue(queue)
}

function getRequestQueue(request: PendingHybridRequest): RequestQueue {
	return request.options.async
		? queues.async
		: queues.navigation
}

async function processRequestQueue(queue: RequestQueue) {
	if (queue.processing) {
		return
	}

	queue.processing = true

	try {
		await processNextRequest(queue)
	} finally {
		queue.processing = false
	}
}

async function processNextRequest(queue: RequestQueue) {
	const request = queue.requests[0]

	if (!request) {
		debug.queue('End of request queue.')
		return
	}

	debug.queue('Processing request', request)
	const response = await sendHybridRequest(request).catch((error: unknown) => {
		const actual = error instanceof Error
			? error
			: new Error('Unknown request transport error.')

		queue.requests.shift()

		// TODO: Revisit transport errors in queue workers and decide whether
		// they should go through the same hook pipeline as response errors.
		request.resolve({
			error: {
				type: actual.constructor.name,
				actual,
			},
		})

		return undefined
	})

	if (!response) {
		return await processNextRequest(queue)
	}

	queue.requests.shift()

	enqueueResponse({
		request,
		response,
	})

	return await processNextRequest(queue)
}

/**
 * Interrupts the queue head only when the queue policy allows it.
 * For non-interruptible queues (e.g. async), this is a no-op.
 */
export function interruptRequestIfNeeded(request: PendingHybridRequest): void {
	const queue = getRequestQueue(request)

	cancelHeadRequest(queue, { interrupted: true, force: false })
}

/**
 * Cancels the current navigation request.
 */
export function cancelNavigationRequest(): void {
	cancelHeadRequest(queues.navigation, { cancelled: true, force: true })
}

interface CancelRequestOptions {
	cancelled?: boolean
	interrupted?: boolean
	force: boolean
}

function cancelHeadRequest(queue: RequestQueue, options: CancelRequestOptions): void {
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

	return queue.interruptible && queue.requests.length >= queue.concurrencyLimit
}
