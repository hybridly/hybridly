import { defineBuildConfig } from 'unbuild'

export default defineBuildConfig({
	entries: [
		'src/index',
		'src/vite',
		'src/vue',
	],
	clean: true,
	declaration: true,
	rollup: {
		emitCJS: true,
		output: {
			exports: 'named',
		},
	},
})
