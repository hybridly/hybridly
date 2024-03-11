import { debug } from '@hybridly/utils'
import type { Axios } from 'axios'
import axios from 'axios'
import { createSerializer } from '../router/history'
import { makeUrl } from '../url'
import type { HybridPayload } from '../router'
import { updateRoutingConfiguration } from '../routing'
import { runHooks } from '../plugins'
import { isDownloadResponse } from '../download'
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
export async function initializeContext(options: RouterContextOptions): Promise<InternalRouterContext> {
	state.initialized = true
	state.context = {
		...options.payload,
		responseErrorModals: options.responseErrorModals,
		serializer: createSerializer(options),
		url: makeUrl(options.payload.url).toString(),
		adapter: {
			...options.adapter,
			updateRoutingConfiguration,
		},
		scrollRegions: [],
		plugins: options.plugins ?? [],
		axios: registerAxios(options.axios ?? axios.create()),
		routing: options.routing,
		preloadCache: new Map(),
		hooks: {},
		memo: {},
	}

	await runHooks('initialized', {}, state.context)

	return getInternalRouterContext()
}

/**
 * Registers an interceptor that assumes `arraybuffer`
 * responses and converts responses to JSON or text.
 */
export function registerAxios(axios: Axios) {
	axios.interceptors.response.use(
		(response) => {
			if (!isDownloadResponse(response)) {
				const text = new TextDecoder().decode(response.data)

				try {
					response.data = JSON.parse(text)
				} catch {
					response.data = text
				}
			}

			return response
		},
		(error) => Promise.reject(error),
	)

	return axios
}

/**
 * Mutates properties at the top-level of the context.
 */
export function setContext(merge: Partial<InternalRouterContext> = {}, options: SetContextOptions = {}): void {
	Object.keys(merge).forEach((key) => {
		Reflect.set(state.context, key, merge[key as keyof InternalRouterContext])
	})

	if (options.propagate !== false) {
		state.context.adapter.onContextUpdate?.(state.context)
	}

	debug.context('Updated context:', { context: state.context, added: merge })
}

/** Gets a payload from the current context. */
export function payloadFromContext(): HybridPayload {
	return {
		url: getRouterContext().url,
		version: getRouterContext().version,
		view: getRouterContext().view,
		dialog: getRouterContext().dialog,
	}
}
