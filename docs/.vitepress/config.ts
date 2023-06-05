import { resolve } from 'node:path'
import { existsSync, readFileSync } from 'node:fs'
import { execSync } from 'node:child_process'
import { defineConfig } from 'vitepress'
import Unocss from 'unocss/vite'

const title = 'Hybridly'
const description = 'Modern solution to develop server-driven, client-rendered applications.'
const url = 'https://hybridly.dev'
const image = `${url}/og.jpg`
const twitter = 'enzoinnocenzi'
const discord = 'https://discord.gg/uZ8eC7kRFV'
const github = 'https://github.com/hybridly/hybridly'

const { version } = JSON.parse(readFileSync(resolve('package.json'), { encoding: 'utf-8' }))
const cleanVersion = String(version).replace(/\.\d+$/, '.x')
const branch = execSync('echo $BRANCH | grep . || git rev-parse --abbrev-ref HEAD')
const hasUpgradeGuide = existsSync(resolve(`./docs/guide/upgrade/${cleanVersion}.md`))

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
		['meta', { name: 'twitter:image', content: image }],
		['meta', { name: 'twitter:site', content: `@${twitter}` }],
		['meta', { name: 'twitter:title', content: title }],
		['meta', { name: 'twitter:description', content: description }],
		['meta', { name: 'theme-color', content: '#646cff' }],
	],

	appearance: 'dark',

	themeConfig: {
		logo: '/logo.svg',

		nav: [
			{ text: 'Guide', link: '/guide/', activeMatch: '/guide/' },
			{ text: 'API', link: '/api/components/router-link', activeMatch: '/api/' },
			{ text: 'Configuration', link: '/configuration/vite' },
			{
				text: 'Resources',
				items: [
					{ text: `v${version}`, link: `${github}/releases/tag/v${version}` },
					{ text: 'Repository', link: `${github}` },
					{ text: 'Demonstration', link: 'https://github.com/hybridly/demo' },
					{ text: 'Preset', link: 'https://github.com/hybridly/preset' },
					{ text: 'Discord', link: discord },
				],
			},
		],

		editLink: {
			pattern: `${github}/edit/${branch}/docs/:path`,
			text: 'Suggest changes to this page',
		},

		socialLinks: [
			{ icon: 'twitter', link: `https://twitter.com/${twitter}` },
			{ icon: 'github', link: `${github}` },
			{ icon: 'discord', link: discord },
		],

		algolia: {
			appId: 'IBVT4QTDXF',
			apiKey: '3b2e1c15434655a09ee419b7204ebd46',
			indexName: 'hybridly',
		},

		sidebar: {
			'/guide/': [
				{
					text: 'Getting started',
					items: [
						{ text: 'Introduction', link: '/guide/' },
						{ text: 'Installation', link: '/guide/installation' },
						{ text: 'Demonstration', link: '/guide/demonstration' },
						{ text: 'TypeScript', link: '/guide/typescript' },
						...(hasUpgradeGuide ? [
							{ text: `Upgrade to ${cleanVersion}`, link: `/guide/upgrade/${cleanVersion}` },
						] : []),
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
						{ text: 'Dialogs', link: '/guide/dialogs' },
						{ text: 'Refining', link: '/guide/refining' },
						{ text: 'Hooks', link: '/guide/hooks' },
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
						{ text: 'Exception handling', link: '/guide/exception-handling' },
						{ text: 'Progress indicator', link: '/guide/progress-indicator' },
						{ text: 'Testing', link: '/guide/testing' },
					],
				},
				{
					text: 'Tooling',
					items: [
						{ text: 'Vue DevTools', link: '/guide/devtools' },
						{ text: 'Code extension', link: '/guide/visual-studio-code' },
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
						{ text: 'Internationalization', link: '/guide/i18n' },
						{ text: 'Case conversion', link: '/guide/case-conversion' },
						{ text: 'Server-side rendering', link: '/guide/ssr' },
						{ text: 'Migrating from Inertia', link: '/guide/migrating-from-inertia' },
						{ text: 'Comparison with Inertia', link: '/guide/comparison-with-inertia' },
					],
				},
			],
			'/api/': [
				{
					text: 'Components',
					items: [
						{ text: '&lt;RouterLink&gt;', link: '/api/components/router-link' },
					],
				},
				{
					text: 'Laravel',
					items: [
						{ text: 'Hybridly', link: '/api/laravel/hybridly' },
						{ text: 'Functions', link: '/api/laravel/functions' },
						{ text: 'Testing', link: '/api/laravel/testing' },
						{ text: 'Directives', link: '/api/laravel/directives' },
					],
				},
				{
					text: 'Router',
					items: [
						{ text: 'Options', link: '/api/router/options' },
						{ text: 'Utils', link: '/api/router/utils' },
						{ text: 'Response', link: '/api/router/response' },
					],
				},
				{
					text: 'Utils',
					items: [
						{ text: 'initializeHybridly', link: '/api/utils/initialize-hybridly' },
						{ text: 'can', link: '/api/utils/can' },
						{ text: 'route', link: '/api/utils/route' },
						{ text: 'registerHook', link: '/api/utils/register-hook' },
					],
				},
				{
					text: 'Composables',
					items: [
						{ text: 'useForm', link: '/api/composables/use-form' },
						{ text: 'useDialog', link: '/api/composables/use-dialog' },
						{ text: 'useProperty', link: '/api/composables/use-property' },
						{ text: 'useRefinements', link: '/api/composables/use-refinements' },
						{ text: 'defineLayout', link: '/api/composables/define-layout' },
						{ text: 'defineLayoutProperties', link: '/api/composables/define-layout-properties' },
						{ text: 'useBackForward', link: '/api/composables/use-back-forward' },
						{ text: 'useHistoryState', link: '/api/composables/use-history-state' },
						{ text: 'useProperties', link: '/api/composables/use-properties' },
						{ text: 'useContext', link: '/api/composables/use-context' },
					],
				},
			],
			'/configuration/': [
				{
					text: 'Configuration',
					items: [
						{ text: 'Vite', link: '/configuration/vite' },
						{ text: 'Architecture', link: '/configuration/architecture' },
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
