import { defineBuildConfig } from 'unbuild'

export default defineBuildConfig({
	entries: [
		'src/index',
	],
	clean: true,
	declaration: true,
	externals: [
		'vite',
		'debug',
		'node:path',
		'node:util',
		'node:child_process',
		'node:fs',
	],
	rollup: {
		emitCJS: true,
	},
})
