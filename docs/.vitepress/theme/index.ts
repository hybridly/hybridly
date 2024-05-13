import 'uno.css'
import '@fontsource-variable/rubik'
import type { Theme } from 'vitepress'
import DefaultTheme from 'vitepress/theme'
import impactHeader from './components/impact-header.vue'
import './styles/brand.css'
import './styles/global.css'

export default {
	extends: DefaultTheme,
	enhanceApp({ app }) {
		app.component('ImpactHeader', impactHeader)
	},
} satisfies Theme
