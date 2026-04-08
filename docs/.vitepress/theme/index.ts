import type { Theme } from 'vitepress'
import DefaultTheme from 'vitepress/theme'
import impactHeader from './components/impact-header.vue'
import '@fontsource/ia-writer-quattro'
import '@fontsource-variable/noto-serif/wght.css'
import '@fontsource-variable/jetbrains-mono/wght.css'
import './theme.css'

export default {
	extends: DefaultTheme,
	enhanceApp({ app }) {
		app.component('ImpactHeader', impactHeader)
	},
} satisfies Theme
