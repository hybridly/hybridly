import { clone } from '@hybridly/utils'
import type { DefaultFormOptions } from '../composables/form'

export const formStore = {
	defaultConfig: {} as DefaultFormOptions,

	setDefaultConfig: (config: DefaultFormOptions) => {
		formStore.defaultConfig = config
	},

	getDefaultConfig: (): DefaultFormOptions => {
		return clone(formStore.defaultConfig)
	},
}
