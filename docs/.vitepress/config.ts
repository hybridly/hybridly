import tailwindcss from '@tailwindcss/vite'
import { execSync } from 'node:child_process'
import { existsSync, readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import icons from 'unplugin-icons/vite'
import { defineConfig } from 'vitepress'
import llmstxt from 'vitepress-plugin-llms'

const title = 'Hybridly'
const description = 'Modern solution to develop server-driven, client-rendered applications.'
const url = 'https://hybridly.dev'
const image = `${url}/og.jpg`
const twitter = 'enzoinnocenzi'
const bluesky = 'innocenzi.dev'
const discord = 'https://discord.gg/uZ8eC7kRFV'
const github = 'https://github.com/hybridly/hybridly'

const { version } = JSON.parse(readFileSync(resolve('package.json'), { encoding: 'utf-8' }))
const [major = '0', minor = '0'] = String(version).split('-')[0].split('.')
const majorVersion = `${major}.${minor}.0`
const cleanVersion = `${major}.${minor}.x`
const branch = execSync('echo $BRANCH | grep . || git rev-parse --abbrev-ref HEAD')
const hasReleaseNotes = existsSync(resolve(`./docs/releases/v${majorVersion}.md`))
const hasUpgradeGuide = existsSync(resolve(`./docs/guide/upgrade/v${cleanVersion}.md`))

export default defineConfig({
	title,
	titleTemplate: `:title — ${title}`,
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

	appearance: 'force-dark',

	themeConfig: {
		logo: '/logo.svg',

		nav: [
			{ text: 'Guide', link: '/guide/', activeMatch: '/guide/' },
			{ text: 'API', link: '/api/components/router-link', activeMatch: '/api/' },
			{ text: 'Configuration', link: '/configuration/vite' },
			{
				text: 'Resources',
				items: [
					{
						text: `v${version}`,
						items: [
							...(hasReleaseNotes ? [{ text: 'Release notes', link: `/releases/v${majorVersion}.md` }] : []),
							...(hasUpgradeGuide ? [{ text: `Upgrade to v${cleanVersion}`, link: `/guide/upgrade/v${cleanVersion}.md` }] : []),
							{ text: 'GitHub', link: `${github}/releases/tag/v${version}` },
						],
					},
					{
						text: 'Useful links',
						items: [
							{ text: 'Discord', link: discord },
							{ text: 'Repository', link: `${github}` },
							{ text: 'Preset', link: 'https://github.com/hybridly/preset' },
						],
					},
				],
			},
		],

		editLink: {
			pattern: `${github}/edit/${branch}/docs/:path`,
			text: 'Suggest changes to this page',
		},

		socialLinks: [
			{ icon: 'bluesky', link: `https://bsky.app/profile/${bluesky}` },
			{ icon: 'twitter', link: `https://twitter.com/${twitter}` },
			{ icon: 'github', link: `${github}` },
			{ icon: 'discord', link: discord },
		],

		search: {
			provider: 'algolia',
			options: {
				appId: 'IBVT4QTDXF',
				apiKey: '3b2e1c15434655a09ee419b7204ebd46',
				indexName: 'hybridly',
			},
		},

		sidebar: {
			'/guide/': [
				{
					text: 'Getting started',
					collapsed: false,
					items: [
						{ text: 'Introduction', link: '/guide/' },
						{ text: 'Installation', link: '/guide/installation' },
					],
				},
				{
					text: 'Essentials',
					collapsed: false,
					items: [
						{ text: 'Routing', link: '/guide/routing' },
						{ text: 'Views & layouts', link: '/guide/views-and-layouts' },
						{ text: 'Navigation', link: '/guide/navigation' },
						{ text: 'Responses', link: '/guide/responses' },
					],
				},
				{
					text: 'Features',
					collapsed: false,
					items: [
						{ text: 'Partial reloads', link: '/guide/partial-reloads' },
						{ text: 'Optimistic responses', link: '/guide/optimistic-responses' },
						{ text: 'Validation', link: '/guide/validation' },
						{ text: 'Authentication', link: '/guide/authentication' },
						{ text: 'Authorization', link: '/guide/authorization' },
						{ text: 'Forms', link: '/guide/forms' },
						{ text: 'Refining', link: '/guide/refining' },
						{ text: 'Tables', link: '/guide/tables' },
						{ text: 'Dialogs', link: '/guide/dialogs' },
						{ text: 'Global properties', link: '/guide/global-properties' },
						{ text: 'File uploads', link: '/guide/file-uploads' },
						{ text: 'Exception handling', link: '/guide/exception-handling' },
						{ text: 'Debugging', link: '/guide/debugging' },
						{ text: 'Testing', link: '/guide/testing' },
					],
				},
				{
					text: 'Extra topics',
					collapsed: true,
					items: [
						{ text: 'TypeScript', link: '/guide/typescript' },
						{ text: 'Progress indicator', link: '/guide/progress-indicator' },
						{ text: 'Hooks', link: '/guide/hooks' },
						{ text: 'Title & meta', link: '/guide/title-and-meta' },
						{ text: 'Scroll management', link: '/guide/scroll-management' },
						{ text: 'Plugins', link: '/guide/plugins' },
						{ text: 'Asset versioning', link: '/guide/asset-versioning' },
						{ text: 'Internationalization', link: '/guide/i18n' },
						{ text: 'Case conversion', link: '/guide/case-conversion' },
						{ text: 'Server-side rendering', link: '/guide/ssr' },
						{ text: 'Architecture', link: '/guide/architecture' },
					],
				},
			],
			'/api/': [
				{
					text: 'Vue',
					collapsed: false,
					items: [
						{ text: '&lt;RouterLink&gt;', link: '/api/components/router-link' },
						{ text: '&lt;Form&gt;', link: '/api/components/form' },
						{ text: '&lt;Deferred&gt;', link: '/api/components/deferred' },
						{ text: '&lt;WhenVisible&gt;', link: '/api/components/when-visible' },
						{ text: 'initializeHybridly', link: '/api/utils/initialize-hybridly' },
						{ text: 'route', link: '/api/utils/route' },
						{ text: 'useForm', link: '/api/utils/use-form' },
						{ text: 'useDialog', link: '/api/utils/use-dialog' },
						{ text: 'useProperty', link: '/api/utils/use-property' },
						{ text: 'setProperty', link: '/api/utils/set-property' },
						{ text: 'useRefinements', link: '/api/utils/use-refinements' },
						{ text: 'useTable', link: '/api/utils/use-table' },
						{ text: 'useBackForward', link: '/api/utils/use-back-forward' },
						{ text: 'useHistoryState', link: '/api/utils/use-history-state' },
						{ text: 'useQueryParameter', link: '/api/utils/use-query-parameter' },
						{ text: 'useQueryParameters', link: '/api/utils/use-query-parameters' },
						{ text: 'useRoute', link: '/api/utils/use-route' },
						{ text: 'useProperties', link: '/api/utils/use-properties' },
						{ text: 'registerHook', link: '/api/utils/register-hook' },
						{ text: 'getRouterContext', link: '/api/utils/get-router-context' },
					],
				},
				{
					text: 'Router',
					collapsed: false,
					items: [
						{ text: 'Options', link: '/api/router/options' },
						{ text: 'Navigation', link: '/api/router/navigation' },
						{ text: 'Response', link: '/api/router/response' },
					],
				},
				{
					text: 'Laravel',
					collapsed: false,
					items: [
						{ text: 'Hybridly', link: '/api/laravel/hybridly' },
						{ text: 'Functions', link: '/api/laravel/functions' },
						{ text: 'Testing', link: '/api/laravel/testing' },
						{ text: 'Directives', link: '/api/laravel/directives' },
					],
				},
			],
			'/configuration/': [
				{
					text: 'Configuration',
					items: [
						{ text: 'Vite', link: '/configuration/vite' },
						{ text: 'Laravel', link: '/configuration/laravel' },
					],
				},
			],
		},
	},

	markdown: {
		theme: 'vitesse-dark',
	},

	vite: {
		plugins: [
			icons({ autoInstall: true }),
			tailwindcss(),
			llmstxt(),
		],
	},
})
