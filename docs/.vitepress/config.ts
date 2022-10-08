import { defineConfig } from 'vitepress'
import Unocss from 'unocss/vite'

export default defineConfig({
	title: 'Monolikit',
	description: 'Modern solution to develop server-driven, client-rendered applications.',

	themeConfig: {
		nav: [
			{ text: 'Guide', link: '/guide/', activeMatch: '/guide/' },
		],

		sidebar: {
			'/guide/': [
				{
					text: 'Guide',
					items: [
						{
							text: 'Inertia',
							link: '/inertia',
						},
					],
				},
			],
		},
	},

	vite: {
		plugins: [
			Unocss(),
		],
	},
})
