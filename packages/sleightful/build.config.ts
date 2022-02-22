import { defineBuildConfig } from 'unbuild'

export default defineBuildConfig({
	entries: [
		'src/index',
		'src/vite',
	],
	clean: true,
	declaration: true,
	externals: [
		'vite',
	],
	rollup: {
		emitCJS: true,
	},
})
