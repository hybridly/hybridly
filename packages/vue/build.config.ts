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
		'monolikit',
		'@vue/shared',
		'axios',
		'virtual:monolikit-router',
	],
	rollup: {
		emitCJS: true,
	},
})
