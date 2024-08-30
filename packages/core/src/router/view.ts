import { debug, merge } from '@hybridly/utils'
import { resetScrollPositions, restoreScrollPositions } from '../scroll'
import { runHooks } from '../plugins'
import { getRouterContext, payloadFromContext, setContext } from '../context'
import type { UrlResolvable } from '../url'
import { normalizeUrl } from '../url'
import { getHistoryMemo, setHistoryState } from './history'
import type { ComponentNavigationOptions, ConditionalNavigationOption, HybridPayload, InternalNavigationOptions } from './types'
import { performHybridNavigation } from './request/request'

/**
 * Makes an internal navigation that swaps the view and updates the context.
 * @internal
 */
export async function navigate(options: InternalNavigationOptions) {
	const context = getRouterContext()

	// Since there is no other way to know prior to the navigation actually being made,
	// we mutate `options` here to add whether there is a dialog or not.
	options.hasDialog ??= !!options.payload?.dialog

	debug.router('Making an internal navigation:', { context, options })
	await runHooks('navigating', {}, options, context)

	// If no request was given, we use the current context instead.
	options.payload ??= payloadFromContext()
	options.payload.view ??= payloadFromContext().view
	options.payload.view.properties = options.properties ?? options.payload.view.properties

	function evaluateConditionalOption<T extends boolean | string>(option?: ConditionalNavigationOption<T>) {
		return typeof option === 'function'
			? option(options)
			: option
	}

	const shouldPreserveState = evaluateConditionalOption(options.preserveState)
	const shouldPreserveScroll = evaluateConditionalOption(options.preserveScroll)
	const shouldReplaceHistory = evaluateConditionalOption(options.replace)
	const shouldReplaceUrl = evaluateConditionalOption(options.preserveUrl)
	const shouldPreserveView = !options.payload.view.component

	// If the navigation was asking to preserve the current state, we also need to
	// update the context's state from the history state.
	if (shouldPreserveState && getHistoryMemo() && options.payload.view.component === context.view.component) {
		debug.history('Setting the memo from this history entry into the current context.')
		setContext({ memo: getHistoryMemo() })
	}

	// If the navigation required the URL to be preserved, we skip its update
	// by replacing the payload URL with the current context URL.
	if (shouldReplaceUrl) {
		debug.router(`Preserving the current URL (${context.url}) instead of navigating to ${options.payload.url}`)
		options.payload!.url = context.url
	}

	// If we didn't receive a component name,
	// we don't swap views and we preserve the url.
	const payload = shouldPreserveView
		? {
			view: {
				component: context.view.component,
				properties: merge(context.view.properties, options.payload.view.properties),
				deferred: context.view.deferred,
			},
			url: context.url,
			version: options.payload.version,
			dialog: context.dialog,
		} satisfies HybridPayload
		: options.payload

	// We merge the new request into the current context. That will replace
	// view, dialog, url and version, so the context is in sync with the
	// navigation that took place.
	setContext({ ...payload, memo: {} })

	// History state must be updated to preserve the expected, native browser behavior.
	// However, in some cases, we just want to swap the views without making an
	// actual navigation. Additionally, we don't want to actually push a new state
	// when navigating to the same URL.
	if (options.updateHistoryState !== false) {
		debug.router(`Target URL is ${context.url}, current window URL is ${window.location.href}.`, { shouldReplaceHistory })
		setHistoryState({ replace: shouldReplaceHistory })
	}

	// If there are deferred properties, we handle them
	// by making a partial-reload after the view component has mounted
	if (context.view.deferred?.length) {
		debug.router('Request has deferred properties, queueing a partial reload:', context.view.deferred)
		context.adapter.executeOnMounted(async () => {
			await performHybridNavigation({
				preserveScroll: true,
				preserveState: true,
				replace: true,
				only: context.view.deferred,
			})
		})
	}

	// Then, we swap the view.
	const viewComponent = !shouldPreserveView
		? await context.adapter.resolveComponent(context.view.component!)
		: undefined

	if (viewComponent) {
		debug.router(`Component [${context.view.component}] resolved to:`, viewComponent)
	}

	await context.adapter.onViewSwap({
		component: viewComponent,
		dialog: context.dialog,
		properties: options.payload?.view?.properties,
		preserveState: shouldPreserveState,
		onMounted: (hookOptions) => runHooks('mounted', {}, { ...options, ...hookOptions }, context),
	})

	if (options.type === 'back-forward') {
		restoreScrollPositions()
	} else if (!shouldPreserveScroll) {
		resetScrollPositions()
	}

	await runHooks('navigated', {}, options, context)
}

/** Performs a local navigation to the given component without a round-trip. */
export async function performLocalNavigation(targetUrl: UrlResolvable, options?: ComponentNavigationOptions) {
	const context = getRouterContext()
	const url = normalizeUrl(targetUrl)

	return await navigate({
		...options,
		type: 'local',
		payload: {
			version: context.version,
			dialog: options?.dialog === false ? undefined : (options?.dialog ?? context.dialog),
			url,
			view: {
				component: options?.component ?? context.view.component,
				properties: options?.properties ?? {},
				deferred: [],
			},
		},
	})
}
