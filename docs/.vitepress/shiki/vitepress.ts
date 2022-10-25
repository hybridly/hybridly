import { addClass, defineProcessor } from 'shiki-processor'

export function createVitepressProcessor() {
	return defineProcessor({
		name: 'vitepress',
		postProcess: ({ code, lang }) => {
			const preRE = /^<pre(.*?)>/
			const styleRE = /<pre .* style=".*"><code>/
			const vueRE = /-vue$/
			const vPre = vueRE.test(lang) ? '' : 'v-pre'
			lang = lang.replace(vueRE, '').toLowerCase()

			return addClass(code, 'vp-code-block', 'pre')
				.replace(preRE, (_, attributes) => `<pre ${vPre}${attributes}>`)
				.replace(styleRE, (_, style) => _.replace(style, ''))
		},
	})
}
