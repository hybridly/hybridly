import { defineBuildConfig } from 'unbuild'

export default defineBuildConfig({
	entries: [
		'src/index',
		'src/vite',
		'src/vue',
	],
	clean: true,
	declaration: true,
	externals: [
		'vite',
		'vue',
	],
	rollup: {
		emitCJS: true,
	},
})
