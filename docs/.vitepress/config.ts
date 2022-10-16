import { resolve } from 'node:path'
import { readFileSync } from 'node:fs'
import { defineConfig } from 'vitepress'
import Unocss from 'unocss/vite'

const title = 'Hybridly'
const description = 'Modern solution to develop server-driven, client-rendered applications.'
const url = 'https://hybridly.dev'
const image = 'TODO'
const twitter = 'enzoinnocenzi'

const { version } = JSON.parse(readFileSync(resolve('package.json'), { encoding: 'utf-8' }))

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

	cleanUrls: 'with-subfolders',

	themeConfig: {
		logo: '/logo.svg',

		nav: [
			{ text: 'Guide', link: '/guide/', activeMatch: '/guide/' },
			{ text: 'API', link: '/api/', activeMatch: '/api/' },
			{ text: 'Configuration', link: '/configuration/', activeMatch: '/configuration/' },
			{
				text: `v${version}`,
				items: [
					{ text: 'Current release', link: `https://github.com/hybridly/hybridly/releases/tag/v${version}` },
					{ text: 'Repository', link: 'https://github.com/hybridly/hybridly' },
					{ text: 'Issues', link: 'https://github.com/hybridly/hybridly/issues' },
				],
			},
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
						{ text: 'What is Hybridly', link: '/guide/' },
						{ text: 'Installation', link: '/guide/installation' },
						{ text: 'Migrating from Inertia', link: '/guide/migrating-from-inertia' },
						{ text: 'Demonstration', link: '/guide/demonstration' },
					],
				},
				{
					text: 'Core concepts',
					items: [
						{ text: 'Pages', link: '/guide/pages' },
						{ text: 'Routing', link: '/guide/routing' },
						{ text: 'Global properties', link: '/guide/global-properties' },
						{ text: 'Partial reloads', link: '/guide/partial-reloads' },
						{ text: 'Persistent properties', link: '/guide/persistent-properties' },
						{ text: 'Forms', link: '/guide/forms' },
						{ text: 'Asset versioning', link: '/guide/asset-versioning' },
					],
				},
				{
					text: 'Essentials',
					items: [
						{ text: 'Validation', link: '/guide/validation' },
						{ text: 'Authentication', link: '/guide/authentication' },
						{ text: 'Authorization', link: '/guide/authorization' },
						{ text: 'Title & meta', link: '/guide/title-and-meta' },
						{ text: 'File uploads', link: '/guide/file-upload' },
						{ text: 'Flash notifications', link: '/guide/flash-notifications' },
						{ text: 'Error handling', link: '/guide/error-handling' },
						{ text: 'CSRF protection', link: '/guide/csrf-protection' },
						{ text: 'Progress indicators', link: '/guide/progress-indicator' },
						{ text: 'Testing', link: '/guide/testing' },
					],
				},
				{
					text: 'Extra topics',
					items: [
						{ text: 'Infinite scrolling', link: '/guide/infinite-scrolling' },
						{ text: 'Scroll management', link: '/guide/scroll-management' },
						{ text: 'Precognition', link: '/guide/precognition' },
						{ text: 'Plugins', link: '/guide/plugins' },
						{ text: 'Server-side rendering', link: '/guide/ssr' },
						{ text: 'Asset versioning', link: '/guide/asset-versioning' },
					],
				},
			],
		},

		footer: {
			message: 'Made with <span class="i-mdi:cards-heart mx-1 inline-block text-pink-300"></span> by <a class="ml-1 underline" href="https://twitter.com/enzoinnocenzi">Enzo Innocenzi</a>',
		},
	},

	vite: {
		plugins: [
			Unocss(),
		],
	},
})
