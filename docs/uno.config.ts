import { defineConfig, presetIcons, presetUno } from 'unocss'
import transformerDirectives from '@unocss/transformer-directives'

export default defineConfig({
	safelist: ['flex', 'ml-1', 'text-center', 'items-center', 'underline', 'i-mdi:cards-heart', 'mx-1', 'inline-block', 'text-pink-300'],
	presets: [
		presetUno(),
		presetIcons({
			autoInstall: true,
		}),
	],
	shortcuts: {
		preface: 'mt-4 text-lg opacity-60',
	},
	theme: {
		fontFamily: {
			title: '"Rubik Variable", var(--vp-font-family-base)',
		},
	},
	transformers: [
		transformerDirectives(),
	],
})
