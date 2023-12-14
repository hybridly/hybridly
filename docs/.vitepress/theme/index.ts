import 'uno.css'
import './styles/brand.css'
import './styles/global.css'
import '@fontsource-variable/rubik'
import type { Theme } from 'vitepress'
import DefaultTheme from 'vitepress/theme'
import impactHeader from './components/impact-header.vue'

export default {
	extends: DefaultTheme,
	enhanceApp({ app }) {
		app.component('ImpactHeader', impactHeader)
	},
} satisfies Theme
