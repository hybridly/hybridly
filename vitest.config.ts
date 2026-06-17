import path from 'node:path'
import { defineConfig } from 'vitest/config'

const alias = (p: string) => path.resolve(__dirname, p)

export default defineConfig({
	optimizeDeps: {
		entries: [],
	},
	resolve: {
		alias: {
			'hybridly': alias('./packages/hybridly/src/'),
			'@hybridly/core': alias('./packages/core/src/'),
			'@hybridly/utils': alias('./packages/utils/src/'),
			'@hybridly/vite': alias('./packages/vite/src/'),
			'@hybridly/vue': alias('./packages/vue/src/'),
		},
	},
	test: {
		isolate: true,
		mockReset: true,
		restoreMocks: true,
		unstubGlobals: true,
		environment: 'happy-dom',
		environmentOptions: {
			happyDOM: {
				url: 'https://bluebird.test',
				settings: {
					navigator: {
						userAgent: 'Mozilla/5.0 (X11; Linux x64) AppleWebKit/537.36 (KHTML, like Gecko) HappyDOM/0.0.0',
					},
				},
			},
		},
	},
})
