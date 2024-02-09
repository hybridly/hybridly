import { router } from '@hybridly/core'
import { computed } from 'vue'
import { dialogStore } from '../stores/dialog'
import { state } from '../stores/state'

/**
 * Exposes utilities related to the dialogs.
 */
export function useDialog() {
	return {
		/** Closes the dialog. */
		close: () => router.dialog.close(),
		/** Closes the dialog without a server round-trip. */
		closeLocally: () => router.dialog.close({ local: true }),
		/** Unmounts the dialog. Should be called after its closing animations. */
		unmount: () => dialogStore.removeComponent(),
		/** Whether the dialog is shown. */
		show: computed(() => dialogStore.state.show.value),
		/** Properties of the dialog. */
		properties: computed(() => state.context.value?.dialog?.properties),
	}
}
