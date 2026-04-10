import ui from '@nuxt/ui/vite'
import tailwindcss from '@tailwindcss/vite'
import hybridly, { hybridlyImports } from 'hybridly/vite'
import { defineConfig } from 'vite'
import inspect from 'vite-plugin-inspect'

export default defineConfig({
	devtools: true,
	plugins: [
		inspect(),
		tailwindcss(),
		ui({
			router: false,
			autoImport: {
				vueTemplate: true,
				dts: '.hybridly/auto-imports.d.ts',
				imports: [
					'vue',
					'@vueuse/core',
					{
						'@unhead/vue': [
							'useHead',
							'useSeoMeta',
						],
						...hybridlyImports,
					},
				],
			},
			components: {
				dts: '.hybridly/components.d.ts',
			},
			ui: {
				colors: {
					primary: 'pink',
					neutral: 'neutral',
					secondary: 'neutral',
				},
				card: {
					defaultVariants: {
						variant: 'subtle',
					},
				},
				button: {
					slots: {
						base: 'not-disabled:cursor-pointer',
					},
					defaultVariants: {
						variant: 'ghost',
					},
				},
			},
		}),
		hybridly(),
	],
	server: {
		watch: {
			ignored: ['**/storage/framework/views/**'],
		},
	},
})
