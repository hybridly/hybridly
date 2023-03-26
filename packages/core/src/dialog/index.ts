import { getInternalRouterContext } from '../context'
import type { HybridRequestOptions } from '../router'
import { performLocalNavigation, performHybridNavigation } from '../router'

export interface CloseDialogOptions extends HybridRequestOptions {
	/**
	 * Close the dialog without a round-trip to the server.
	 * @default false
	 */
	local?: boolean
}

/**
 * Closes the dialog.
 */
export async function closeDialog(options?: CloseDialogOptions) {
	const context = getInternalRouterContext()
	const url = context.dialog?.redirectUrl ?? context.dialog?.baseUrl

	if (!url) {
		return
	}

	context.adapter.onDialogClose?.(context)

	if (options?.local === true) {
		return await performLocalNavigation(url, {
			preserveScroll: true,
			preserveState: true,
			dialog: false,
			component: context.view.component,
			properties: context.view.properties,
			...options,
		})
	}

	return await performHybridNavigation({
		url,
		preserveScroll: true,
		preserveState: true,
		...options,
	})
}
