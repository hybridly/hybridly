import { defineBuildConfig } from 'unbuild'

export default defineBuildConfig({
	entries: [
		'src/index',
		'src/vite',
		'src/config',
		'src/vue',
	],
	clean: true,
	declaration: true,
	externals: [
		'vite',
		'vue',
		'@hybridly/progress-plugin',
	],
	rollup: {
		emitCJS: true,
	},
})
