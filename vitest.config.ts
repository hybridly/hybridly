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
			'@hybridly/vite': alias('./packages/vite/src/'),
		},
	},
	test: {
		isolate: false,
		mockReset: true,
		restoreMocks: true,
		unstubGlobals: true,
		environment: 'happy-dom',
		threads: false,
		setupFiles: [
			alias('./packages/core/test/setup.ts'),
		],
	},
})
