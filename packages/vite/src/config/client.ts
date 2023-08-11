import type { DynamicConfiguration } from '@hybridly/core'

export function getClientCode(config: DynamicConfiguration) {
	const paths = config.components.views
		.map(({ path }) => `"~/${path.replaceAll("\\", "/")}"`)
		.join(",");

	return `
		import { initializeHybridly as init } from 'hybridly/vue'

		export function initializeHybridly(config) {
			return init({
				...${JSON.stringify(config)},
				imported: import.meta.glob([${paths}], { eager: ${config.components.eager ?? true} }),
				...config,
			})
		}
 `
}
