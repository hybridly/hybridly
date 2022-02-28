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
	],
	rollup: {
		emitCJS: true,
	},
})
