import { debug } from '@hybridly/utils'
import { createSerializer } from '../router/history'
import { makeUrl } from '../url'
import type { VisitPayload } from '../router'
import type { RouterContext, InternalRouterContext, RouterContextOptions, SetContextOptions } from './types'

const state = {
	initialized: false,
	context: {} as InternalRouterContext,
}

/** Gets the current context. */
export function getRouterContext(): RouterContext {
	return getInternalRouterContext()
}

/** Gets the current context, but not in read-only. */
export function getInternalRouterContext(): InternalRouterContext {
	if (!state.initialized) {
		throw new Error('Hybridly is not initialized.')
	}

	return state.context
}

/** Initializes the context. */
export function initializeContext(options: RouterContextOptions): InternalRouterContext {
	state.initialized = true
	state.context = {
		...options.payload,
		serializer: createSerializer(options),
		url: makeUrl(options.payload.url).toString(),
		adapter: options.adapter,
		scrollRegions: [],
		state: {},
	}

	return getInternalRouterContext()
}

/**
 * Mutates properties at the top-level of the context.
 */
export function setContext(merge: Partial<InternalRouterContext> = {}, options: SetContextOptions = {}): void {
	Object.keys(merge).forEach((key) => {
		Reflect.set(state.context, key, merge[key as keyof InternalRouterContext])
	})

	if (options.propagate !== false) {
		state.context.adapter.update?.(state.context)
	}

	debug.context('Updated context:', { context: state.context, added: merge })
}

/** Gets a visit payload from the current context. */
export function payloadFromContext(): VisitPayload {
	return {
		url: getRouterContext().url,
		version: getRouterContext().version,
		view: getRouterContext().view,
		dialog: getRouterContext().dialog,
	}
}
