import { debug } from '@hybridly/utils'
import { enqueueResponse } from '../response/response-manager'
import type { PendingHybridRequest } from '../types'
import { sendHybridRequest } from './request'

const activeRequests = {
	navigation: undefined as PendingHybridRequest | undefined,
	async: new Map<string, PendingHybridRequest>(),
	asyncGroups: new Map<string, Set<string>>(),
}

/**
 * Registers and starts a request.
 */
export function enqueueRequest(request: PendingHybridRequest) {
	debug.queue('Enqueuing request', request)
	interruptRequestIfNeeded(request)

	if (isAsyncRequest(request)) {
		registerAsyncRequest(request)
		void processRequest(request, () => unregisterAsyncRequest(request))
		return
	}

	activeRequests.navigation = request
	void processRequest(request, () => {
		if (activeRequests.navigation?.id === request.id) {
			activeRequests.navigation = undefined
		}
	})
}

function isAsyncRequest(request: PendingHybridRequest): boolean {
	return request.options.mode === 'async'
}

function registerAsyncRequest(request: PendingHybridRequest): void {
	activeRequests.async.set(request.id, request)

	const group = request.options.group

	if (!group) {
		return
	}

	if (!activeRequests.asyncGroups.has(group)) {
		activeRequests.asyncGroups.set(group, new Set())
	}

	activeRequests.asyncGroups.get(group)?.add(request.id)
}

function unregisterAsyncRequest(request: PendingHybridRequest): void {
	activeRequests.async.delete(request.id)

	const group = request.options.group

	if (!group) {
		return
	}

	const requests = activeRequests.asyncGroups.get(group)

	if (!requests) {
		return
	}

	requests.delete(request.id)

	if (requests.size === 0) {
		activeRequests.asyncGroups.delete(group)
	}
}

function processRequest(request: PendingHybridRequest, onFinally: () => void): Promise<void> {
	debug.queue('Processing request', request)

	return sendHybridRequest(request)
		.then((response) => {
			enqueueResponse({
				request,
				response,
			})
		})
		.catch((error: unknown) => {
			handleTransportError(request, error)
		})
		.finally(() => {
			request.completed = true
			onFinally()
		})
}

function handleTransportError(request: PendingHybridRequest, error: unknown): void {
	const actual = error instanceof Error
		? error
		: new Error('Unknown request transport error.')

	// TODO: might need revisiting
	request.resolve({
		error: {
			type: actual.constructor.name,
			actual,
		},
	})
}

/**
 * Interrupts active requests according to the starting request's policy.
 */
export function interruptRequestIfNeeded(request: PendingHybridRequest): void {
	if (isAsyncRequest(request)) {
		interruptAsyncRequestsOnStart(request)
		return
	}

	interruptNavigationRequest({ interrupted: true })
	interruptAsyncRequestsCancelledByNavigation()
	interruptAsyncRequestsOnStart(request)
}

function interruptNavigationRequest(options: CancelRequestOptions): void {
	const request = activeRequests.navigation

	if (!request) {
		return
	}

	activeRequests.navigation = undefined
	cancelRequest(request, options)
}

function interruptAsyncRequestsCancelledByNavigation(): void {
	for (const request of activeRequests.async.values()) {
		if (request.options.cancelOnNavigation) {
			interruptAsyncRequest(request, { interrupted: true })
		}
	}
}

function interruptAsyncRequestsOnStart(request: PendingHybridRequest): void {
	debug.queue('Interrupting async requests', request)

	if (request.options.interruptAsyncOnStart === 'none') {
		return
	}

	if (request.options.interruptAsyncOnStart === 'all') {
		for (const current of activeRequests.async.values()) {
			interruptAsyncRequest(current, { interrupted: true })
		}
		return
	}

	if (!request.options.group) {
		return
	}

	for (const requestId of activeRequests.asyncGroups.get(request.options.group) ?? []) {
		const current = activeRequests.async.get(requestId)

		if (current) {
			interruptAsyncRequest(current, { interrupted: true })
		}
	}
}

function interruptAsyncRequest(request: PendingHybridRequest, options: CancelRequestOptions): void {
	unregisterAsyncRequest(request)
	cancelRequest(request, options)
}

/**
 * Cancels the current navigation request.
 */
export function cancelNavigationRequest(): void {
	interruptNavigationRequest({ cancelled: true })
}

interface CancelRequestOptions {
	/**
	 * Gracefully cancelled.
	 */
	cancelled?: boolean
	/**
	 * Interrupted by another request.
	 */
	interrupted?: boolean
}

function cancelRequest(request: PendingHybridRequest, options: CancelRequestOptions): void {
	request.completed = false
	request.cancelled = options.cancelled ?? false
	request.interrupted = options.interrupted ?? false
	request.controller.abort()
}
