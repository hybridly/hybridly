<script setup lang="ts">
interface Feature {
	title: string
	description: string
	url?: string
	icon?: string
}

const features: Feature[] = [
	{
		icon: 'i-mdi:language-typescript',
		title: 'TypeScript ready',
		description:
			"If it's typable, it's typed. Hybridly supports typing paginators, routes, authorizations and global properties.",
		url: '/guide/typescript',
	},
	{
		icon: 'i-mdi:dock-window',
		title: 'Dialogs',
		description:
			'Effortlessly implement URL-based dialogs, just like you would implement a page component.',
		url: '/guide/dialogs',
	},
	{
		icon: 'i-mdi:lock-outline',
		title: 'Authorization',
		description:
			'Hybridly extends <a href="https://github.com/spatie/laravel-data">data objects</a> to provide an interface for generating typings for authorizations.',
		url: '/guide/authorization',
	},
	{
		icon: 'i-mdi:web',
		title: 'Typed routes',
		description:
			"Define your routes server-side like you're used to, Hybridly takes care of generating types for them.",
		url: '/guide/routing',
	},
	{
		icon: 'i-heroicons:funnel',
		title: 'Refining',
		description:
			'Filter and sort queries using the built-in refinement tools and build your UI around the provided composable.',
		url: '/guide/refining',
	},
	{
		icon: 'i-mdi:table-large',
		title: 'Tables',
		description:
			'Build datatables with full control over their appearance, including inline and bulk actions, filtering, sorting and more.',
		url: '/guide/tables',
	},
	{
		icon: 'i-mdi:shape',
		title: 'Flexible architecture',
		description:
			"Whether you prefer Laravel's default architecture, modules, slices, or something custom, Hybridly supports it.",
		url: '/guide/architecture',
	},
	{
		icon: 'i-mdi:microsoft-visual-studio-code',
		title: 'Visual Studio Code',
		description:
			'Auto-complete component names, and navigate between controllers, layouts and views with a single click.',
		url: '/guide/visual-studio-code',
	},
	{
		icon: 'i-mdi:vuejs',
		title: 'Vue DevTools',
		description: 'Integrates with Vue DevTools for painless property inspection and debugging.',
		url: '/guide/devtools',
	},
	// {
	// 	icon: 'i-mdi:code-braces',
	// 	title: 'Made for developers',
	// 	description: 'Hybridly is packed with quality-of-life features that should drastically improve your developer experience.',
	// 	url: '/guide/comparison-with-inertia',
	// },
]
</script>

<template>
	<client-only>
		<section class="relative flex flex-col mx-auto mt-20 lg:mt-40 md:px-10 xl:px-0 max-w-5xl size-full marketing">
			<!-- Hero -->
			<div class="flex lg:flex-row flex-col justify-between items-center lg:items-start gap-x-40 px-6 lg:px-0 w-full">
				<!-- Left block -->
				<section class="group max-w-2xl *:transition *:duration-500 *:ease-in-out grow">
					<!-- Title -->
					<h1 class="bg-clip-text font-title font-medium text-transparent text-5xl lg:text-7xl lg:text-left text-center uppercase title title-bg">
						Hybridly
					</h1>
					<!-- Subtitle -->
					<p class="mt-4 font-mono font-medium text-xl lg:text-2xl lg:text-left text-center">
						Server-driven, client-rendered applications.
					</p>
					<!-- Description -->
					<p class="opacity-60 mt-4 font-medium text-lg lg:text-xl lg:text-left text-center">
						Build single-page applications the classic way. No API, no route duplication, no
						front-end state. Fully server-driven.
					</p>
					<!-- Actions -->
					<div class="flex flex-wrap justify-center lg:justify-start gap-4 mt-10">
						<a
							class="group flex items-center bg-[#FFEAEF] hover:bg-[#FFDBE3] dark:bg-(--vp-c-brand-light) dark:hover:bg-(--vp-c-brand-lighter) px-4.5 py-2.5 rounded-2xl font-semibold text-[#DB7DA8] hover:text-[#D6689B] dark:hover:text-(--vp-c-bg) dark:text-(--vp-c-bg) transition"
							href="/guide/"
						>
							Get started
							<svg
								class="ml-2 transition group-hover:translate-x-1"
								xmlns="http://www.w3.org/2000/svg"
								width="10"
								height="10"
								viewBox="0 0 24 24"
							>
								<path
									d="M13.025 1l-2.847 2.828 6.176 6.176h-16.354v3.992h16.354l-6.176 6.176 2.847 2.828 10.975-11z"
									fill="currentcolor"
								/>
							</svg>
						</a>
						<a
							class="group flex items-center bg-(--vp-c-bg-soft) hover:bg-(--vp-c-bg-mute) px-4.5 py-2 rounded-2xl font-medium text-(--vp-c-text-1) transition"
							href="https://github.com/hybridly/hybridly"
						>See on GitHub</a>
					</div>
				</section>

				<!-- Logo block -->
				<div class="group relative flex justify-center items-center order-first lg:order-last lg:mt-20 lg:mr-20 mb-20 w-30 lg:w-55 h-30 lg:h-55 hover:scale-105 transition duration-300 shrink-0">
					<img
						class="z-1 absolute size-full group-hover:scale-85 transition duration-300"
						src="/logo.svg"
					/>
					<div class="absolute -inset-5 opacity-40 dark:opacity-70 rotate-45 image-bg" />
				</div>
			</div>

			<!-- Features -->
			<section class="gap-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 mt-24 px-8 lg:px-0">
				<template v-for="feature in features" :key="feature.title">
					<component
						:is="feature.url ? 'a' : 'div'"
						class="group relative flex flex-col justify-center gap-4 bg-(--ui-bg-elevated) p-6 rounded-lg ring-1 ring-(--ui-border)"
						:class="{ 'cursor-pointer ': feature.url }"
						:href="feature.url"
					>
						<div class="top-2 right-2 absolute flex justify-center items-center p-2 rounded w-18 h-18 shrink-0">
							<div
								:class="feature.icon"
								class="opacity-20 dark:group-hover:opacity-20 group-hover:opacity-100 size-full text-(--ui-text-highlighted) transition"
							/>
						</div>
						<div>
							<span
								class="inline-block text-(--ui-text-muted) group-hover:text-(--ui-text) transition font-mono uppercase font-medium tracking-wide"
								v-text="feature.title"
							/>
							<!-- eslint-disable-next-line vue/no-v-html -->
							<p
								class="mt-2 font-medium text-(--ui-text-dimmed) group-hover:text-(--ui-text-highlighted) text-sm leading-relaxed transition"
								v-html="feature.description"
							/>
						</div>
					</component>
				</template>
			</section>
		</section>
	</client-only>
</template>
