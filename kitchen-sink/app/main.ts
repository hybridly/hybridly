import ui from '@nuxt/ui/vue-plugin'
import { createHead } from '@unhead/vue/client'
import { type Method, router } from 'hybridly'
import { initializeHybridly } from 'virtual:hybridly/config'
import '@fontsource-variable/jetbrains-mono/wght.css'
import './main.css'

initializeHybridly({
	enhanceVue: (vue) => {
		const head = createHead()
		head.push({
			titleTemplate: (title) => `${title ?? ''} — Kitchen sink`.replace(/^ — /, ''),
		})

		vue.use(head)
		vue.use(ui, {
			router: (event: MouseEvent, { href, external }: { href: string; external: boolean }) => {
				const target = event.currentTarget as HTMLElement
				const method = target.dataset.method as Method || 'GET'
				const replace = !!target.dataset.replace
				const viewTransition = target.dataset.viewTransition ?? undefined

				if (external || target.dataset.external) {
					return
				}

				event.preventDefault()

				router.navigate({
					url: href,
					method,
					preserveState: method !== 'GET',
					replace,
					viewTransition,
				})
			},
		})
	},
})
