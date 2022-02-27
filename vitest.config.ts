import path from 'node:path'
import { defineConfig } from 'vitest/config'

const alias = (p: string) => path.resolve(__dirname, p)

export default defineConfig({
	optimizeDeps: {
		entries: [],
	},
	resolve: {
		alias: {
			'sleightful': alias('./packages/sleightful/src/'),
			'@sleightful/core': alias('./packages/core/src/'),
			'@sleightful/vite': alias('./packages/vite/src/'),
		},
	},
	test: {
		isolate: false,
		restoreMocks: true,
		environment: 'happy-dom',
		threads: false,
		setupFiles: [
			alias('./packages/core/test/setup.ts'),
		],
	},
})
