import { resolve } from 'node:path'
import { readFileSync } from 'node:fs'
import { defineConfig } from 'vitepress'
import Unocss from 'unocss/vite'
import highlighter from './shiki-tags/highlighter'

const title = 'Hybridly'
const description = 'Modern solution to develop server-driven, client-rendered applications.'
const url = 'https://hybridly.dev'
const image = '/og.jpg'
const twitter = 'enzoinnocenzi'
const discord = 'https://discord.gg/uZ8eC7kRFV'
const github = 'https://github.com/hybridly/hybridly'

const { version } = JSON.parse(readFileSync(resolve('package.json'), { encoding: 'utf-8' }))

export default async() => defineConfig({
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
		// Remove after https://github.com/vuejs/vitepress/pull/1498 is merged
		['script', {}, 'window?.localStorage?.setItem("vitepress-theme-appearance", window?.localStorage?.getItem("vitepress-theme-appearance") ?? "dark")'],
	],

	themeConfig: {
		logo: '/logo.svg',

		nav: [
			{ text: 'Guide', link: '/guide/', activeMatch: '/guide/' },
			{ text: 'API', link: '/api/components/router-link', activeMatch: '/api/' },
			{
				text: 'Resources',
				items: [
					{ text: `v${version}`, link: `${github}/releases/tag/v${version}` },
					{ text: 'Repository', link: `${github}` },
					{ text: 'Demonstration', link: 'https://github.com/hybridly/demo' },
					{ text: 'Discord', link: discord },
				],
			},
		],

		editLink: {
			pattern: `${github}/edit/main/docs/:path`,
			text: 'Suggest changes to this page',
		},

		socialLinks: [
			{ icon: 'twitter', link: `https://twitter.com/${twitter}` },
			{ icon: 'github', link: `${github}` },
			{ icon: 'discord', link: discord },
		],

		sidebar: {
			'/guide/': [
				{
					text: 'Getting started',
					items: [
						{ text: 'What is Hybridly', link: '/guide/' },
						{ text: 'Installation', link: '/guide/installation' },
						{ text: 'Demonstration', link: '/guide/demonstration' },
						{ text: 'TypeScript', link: '/guide/typescript' },
					],
				},
				{
					text: 'Core concepts',
					items: [
						{ text: 'Routing', link: '/guide/routing' },
						{ text: 'Responses', link: '/guide/responses' },
						{ text: 'Navigation', link: '/guide/navigation' },
						{ text: 'Pages & layouts', link: '/guide/pages-and-layouts' },
						{ text: 'Global properties', link: '/guide/global-properties' },
						{ text: 'Partial reloads', link: '/guide/partial-reloads' },
						{ text: 'Persistent properties', link: '/guide/persistent-properties' },
						{ text: 'Forms', link: '/guide/forms' },
						{ text: 'Hooks', link: '/guide/hooks' },
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
						{ text: 'File uploads', link: '/guide/file-uploads' },
						{ text: 'Flash notifications', link: '/guide/flash-notifications' },
						{ text: 'Error handling', link: '/guide/error-handling' },
						{ text: 'Progress indicator', link: '/guide/progress-indicator' },
						{ text: 'Testing', link: '/guide/testing' },
					],
				},
				{
					text: 'Extra topics',
					items: [
						{ text: 'Preserving URLs', link: '/guide/preserving-urls' },
						{ text: 'Scroll management', link: '/guide/scroll-management' },
						{ text: 'Precognition', link: '/guide/precognition' },
						{ text: 'Plugins', link: '/guide/plugins' },
						{ text: 'Asset versioning', link: '/guide/asset-versioning' },
						{ text: 'Vue DevTools', link: '/guide/devtools' },
						{ text: 'Server-side rendering', link: '/guide/ssr' },
						{ text: 'Migrating from Inertia', link: '/guide/migrating-from-inertia' },
						{ text: 'Comparison with Inertia', link: '/guide/comparison-with-inertia' },
						{ text: 'Roadmap', link: 'https://github.com/orgs/hybridly/projects/1/views/1' },
					],
				},
			],
			'/api/': [
				{
					text: 'Components',
					items: [
						{ text: '<RouterLink>', link: '/api/components/router-link' },
					],
				},
				{
					text: 'Laravel',
					items: [
						{ text: 'Hybridly', link: '/api/laravel/hybridly' },
						{ text: 'Global functions', link: '/api/laravel/global-functions' },
						{ text: 'Testing', link: '/api/laravel/testing' },
					],
				},
				{
					text: 'Utils',
					items: [
						{ text: 'can', link: '/api/utils/can' },
						{ text: 'route', link: '/api/utils/route' },
						{ text: 'router', link: '/api/utils/router' },
						{ text: 'registerHook', link: '/api/utils/register-hook' },
					],
				},
				{
					text: 'Composables',
					items: [
						{ text: 'useForm', link: '/api/composables/use-form' },
						{ text: 'useProperty', link: '/api/composables/use-property' },
						{ text: 'defineLayout', link: '/api/composables/define-layout' },
						{ text: 'defineLayoutProperties', link: '/api/composables/define-layout-properties' },
						{ text: 'useBackForward', link: '/api/composables/use-back-forward' },
						{ text: 'useHistoryState', link: '/api/composables/use-history-state' },
						{ text: 'useProperties', link: '/api/composables/use-properties' },
						{ text: 'useContext', link: '/api/composables/use-context' },
					],
				},
				{
					text: 'Vite plugin',
					items: [
						{ text: 'layout', link: '/api/vite/layout' },
						{ text: 'router', link: '/api/vite/router' },
					],
				},
			],
		},

		footer: {
			message: 'Made with <span class="i-mdi:cards-heart mx-1 inline-block text-pink-300"></span> by <a class="ml-1 underline" href="https://twitter.com/enzoinnocenzi">Enzo Innocenzi</a>',
		},
	},

	markdown: {
		highlight: await highlighter(),
	},

	vite: {
		plugins: [
			Unocss(),
		],
	},
})
