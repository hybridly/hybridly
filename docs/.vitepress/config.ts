import { defineConfig } from 'vitepress'
import Unocss from 'unocss/vite'
const title = 'Hybridly'
const description = 'Modern solution to develop server-driven, client-rendered applications.'
const url = 'https://hybridly.dev'
const image = 'TODO'
const twitter = 'enzoinnocenzi'

export default defineConfig({
	title,
	description,

	head: [
		['link', { rel: 'icon', type: 'image/svg+xml', href: '/logo.svg' }],
		['meta', { property: 'og:type', content: 'website' }],
		['meta', { property: 'og:title', content: title }],
		['meta', { property: 'og:image', content: image }],
		['meta', { property: 'og:url', content: url }],
		['meta', { property: 'og:description', content: description }],
		['meta', { name: 'twitter:card', content: 'summary_large_image' }],
		['meta', { name: 'twitter:site', content: `@${twitter}` }],
		['meta', { name: 'theme-color', content: '#646cff' }],
	],

	themeConfig: {
		nav: [
			{ text: 'Guide', link: '/guide/', activeMatch: '/guide/' },
		],

		editLink: {
			pattern: 'https://github.com/hybridly/hybridly/edit/main/docs/:path',
			text: 'Suggest changes to this page',
		},

		socialLinks: [
			{ icon: 'twitter', link: `https://twitter.com/${twitter}` },
			{ icon: 'github', link: 'https://github.com/hybridly/hybridly' },
		],

		sidebar: {
			'/guide/': [
				{
					text: 'Getting started',
					items: [
						{ text: 'What is Hybridly', link: '/what-is-hybridly' },
						{ text: 'Installation', link: '/installation' },
						{ text: 'Moving from Inertia', link: '/moving-from-inertia' },
						{ text: 'Demonstration', link: '/demonstration' },
					],
				},
				{
					text: 'Features',
					items: [
						{ text: 'Sharing data', link: '/sharing-data' },
						{ text: 'Routing', link: '/routing' },
						{ text: 'Pages', link: '/pages' },
						{ text: 'Title & meta', link: '/title-meta' },
						{ text: 'Forms', link: '/forms' },
						{ text: 'File upload', link: '/file-upload' },
						{ text: 'Validation', link: '/validation' },
						{ text: 'Infinite scrolling', link: '/infinite-scrolling' },
						{ text: 'Authentication', link: '/authentication' },
						{ text: 'Authorization', link: '/authorization' },
						{ text: 'Partial reloads', link: '/partial-reloads' },
						{ text: 'Error handling', link: '/error-handling' },
						{ text: 'Asset versioning', link: '/asset-versioning' },
						{ text: 'Progress indicators', link: '/progress-indicator' },
						{ text: 'Testing', link: '/testing' },
					],
				},
				{
					text: 'Advanced',
					items: [
						{ text: 'Scroll management', link: '/scroll-management' },
						{ text: 'Precognition', link: '/precognition' },
						{ text: 'Plugins', link: '/plugins' },
						{ text: 'Server-side rendering', link: '/ssr' },
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
