import { router } from '@hybridly/core'
import type { CloseDialogOptions } from '@hybridly/core'
import { computed } from 'vue'
import { dialogStore } from '../stores/dialog'
import { state } from '../stores/state'

/**
 * Exposes utilities related to the dialogs.
 */
export function useDialog() {
	return {
		/** Closes the dialog. */
		close: (options?: CloseDialogOptions) => router.dialog.close(options),
		/** Closes the dialog without a server round-trip. */
		closeLocally: (options?: CloseDialogOptions) => router.dialog.close({ ...options, local: true }),
		/** Unmounts the dialog. Should be called after its closing animations. */
		unmount: () => dialogStore.removeComponent(),
		/** Whether the dialog is shown. */
		show: computed({ get: () => dialogStore.state.show.value, set: (v) => !v ? router.dialog.close({ local: true }) : null }),
		/** Properties of the dialog. */
		properties: computed(() => state.context.value?.dialog?.properties),
	}
}
