import { defineBuildConfig } from 'unbuild'

export default defineBuildConfig({
	entries: [
		'src/index',
		'src/vite',
		'src/vue',
		'src/auto-imports',
		'src/resolver',
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
