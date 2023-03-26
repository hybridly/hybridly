import { defineBuildConfig } from 'unbuild'

export default defineBuildConfig({
	entries: [
		'src/index',
	],
	clean: true,
	declaration: true,
	externals: [
		'vue',
		'vite',
		'esbuild',
		'rollup',
		'postcss',
		'source-map-js',
		'type-fest',
		'hybridly',
		'@vue/shared',
		'axios',
		'@clickbar/dot-diver',
	],
	rollup: {
		emitCJS: true,
	},
})
