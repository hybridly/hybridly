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
		title: 'TypeScript support',
		description: "If it's typable, it's typed. Hybridly supports typing paginators, routes, authorizations and global properties.",
		url: '/guide/typescript',
	},
	{
		icon: 'i-mdi:web',
		title: 'Routes',
		description: 'Define your routes server-side like you\'re used to, Hybridly takes care of generating types for them.',
		url: '/guide/routing',
	},
	{
		icon: 'i-mdi:lock-outline',
		title: 'Authorization',
		description: 'Hybridly extends <a href="https://github.com/spatie/laravel-data">data objects</a> to provide an interface for generating typings for authorizations.',
		url: '/guide/authorization',
	},
	{
		icon: 'i-mdi:magnify',
		title: 'Vue DevToools',
		description: 'Integrates with Vue DevTools for painless property inspection and debugging.',
		url: '/guide/devtools',
	},
	{
		icon: 'i-mdi:chevron-double-right',
		title: 'Built on battle-tested tech',
		description: 'Hybridly is a fork of Inertia. Benefit from <abbr title="Except server-side rendering. For now.">all of its features</abbr>, plus a lot of new, unique functionalities.',
		url: '/guide/#about-inertia',
	},
	{
		icon: 'i-mdi:human-greeting-variant',
		title: 'Painless migration',
		description: 'Migrating is a matter of installing different dependencies and replacing the API.',
		url: '/guide/migrating-from-inertia',
	},
]
</script>

<template>
	<ClientOnly>
		<section class="mx-auto mt-20 flex h-full w-full max-w-5xl flex-col lg:mt-40">
			<!-- Hero -->
			<div class="flex w-full flex-col items-center justify-between gap-x-40 px-6 lg:flex-row lg:items-start lg:px-0">
				<!-- Left block -->
				<section class="max-w-2xl grow">
					<!-- Title -->
					<h1 class="title font-title title-bg bg-clip-text text-center text-5xl font-medium uppercase text-transparent lg:text-left lg:text-8xl">
						Hybridly
					</h1>
					<!-- Subtitle -->
					<p class="mt-2 text-center text-2xl font-medium lg:text-left lg:text-3xl">
						Server-driven, client-rendered applications.
					</p>
					<!-- Description -->
					<p class="mt-4 text-center text-lg font-medium opacity-60 lg:text-left lg:text-2xl">
						Build single-page applications the classic way. No API, no route duplication, no front-end state. Fully server-driven.
					</p>
					<!-- Actions -->
					<div class="mt-10 flex flex-wrap justify-center gap-4 lg:justify-start">
						<a class="group px-4.5 dark:text-[var(--vp-c-bg)] dark:hover:text-[var(--vp-c-bg)] dark:hover:bg-[var(--vp-c-brand-lighter)] dark:bg-[var(--vp-c-brand-light)] flex items-center rounded-full bg-[#FFEAEF] py-2.5 text-sm font-semibold text-[#DB7DA8] transition hover:bg-[#FFDBE3] hover:text-[#D6689B]" href="/guide/">
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
						<a class="bg-[var(--vp-c-bg-soft)] hover:bg-[var(--vp-c-bg-mute)] group text-[var(--vp-c-text-1)] px-4.5 border-[var(--vp-button-alt-border)] flex items-center rounded-full border py-2 text-sm font-medium transition" href="/guide/demonstration">Demonstration</a>
						<a class="bg-[var(--vp-c-bg-soft)] hover:bg-[var(--vp-c-bg-mute)] group text-[var(--vp-c-text-1)] px-4.5 border-[var(--vp-button-alt-border)] flex items-center rounded-full border py-2 text-sm font-medium transition" href="https://github.com/hybridly/hybridly">See on GitHub</a>
					</div>
				</section>

				<!-- Logo block -->
				<div class="lg:h-55 lg:w-55 h-30 w-30 relative order-first mb-20 flex shrink-0 items-center justify-center lg:order-last lg:mt-20 lg:mr-20">
					<img class="absolute z-[1] h-full w-full" src="/logo.svg" />
					<div class="image-bg absolute -inset-5 rotate-45 opacity-40 dark:opacity-70" />
				</div>
			</div>

			<!-- Features -->
			<section class="mt-24 grid grid-cols-1 gap-8 px-8 sm:grid-cols-2 lg:grid-cols-3 lg:px-0">
				<template v-for="feature in features" :key="feature.title">
					<component
						:is="feature.url ? 'a' : 'div'"
						class="bg-[var(--vp-c-bg-soft)] group relative flex flex-col justify-center gap-4 rounded-lg p-6"
						:class="{
							'transition cursor-pointer hover:bg-[var(--vp-c-bg-mute)]': feature.url
						}"
						:href="feature.url"
					>
						<div class="h-25 w-25 absolute right-5 flex shrink-0 items-center justify-center rounded p-2">
							<div :class="feature.icon" class="text-[var(--vp-c-text-2)] h-full w-full opacity-0 transition group-hover:opacity-5 dark:group-hover:opacity-10" />
						</div>
						<div>
							<span class="font-semibold" v-text="feature.title" />
							<!-- eslint-disable-next-line vue/no-v-html -->
							<p class="text-[var(--vp-c-text-2)] mt-2 text-sm font-medium leading-relaxed" v-html="feature.description" />
						</div>
					</component>
				</template>
			</section>
		</section>
	</ClientOnly>
</template>

<style scoped>
.title-bg {
	animation: title-gradient 20s ease infinite;
	background-size: 400% 400%;
  background-image: linear-gradient(
		-45deg,
		var(--vp-logo-shadow-1),
		var(--vp-logo-shadow-2),
		var(--vp-logo-shadow-3),
		var(--vp-logo-shadow-4),
		var(--vp-logo-shadow-2),
		var(--vp-logo-shadow-4),
		var(--vp-logo-shadow-3),
		var(--vp-logo-shadow-2)
	);
}

@keyframes title-gradient {
	0% {
		background-position: 0% 50%;
	}
	50% {
		background-position: 100% 50%;
	}
	100% {
		background-position: 0% 50%;
	}
}

.image-bg {
  filter: blur(40px);
	animation: image-gradient 10s ease infinite;
	background-size: 400% 400%;
  background-image: linear-gradient(
		-45deg,
		var(--vp-logo-shadow-1),
		var(--vp-logo-shadow-2),
		var(--vp-logo-shadow-3),
		var(--vp-logo-shadow-4),
		var(--vp-logo-shadow-2),
		var(--vp-logo-shadow-4),
		var(--vp-logo-shadow-3),
		var(--vp-logo-shadow-2)
	);
}

@keyframes image-gradient {
	0% {
		background-position: 0% 50%;
		border-radius: 75px;
		transform: rotate(0deg);
	}
	50% {
		background-position: 100% 50%;
		border-radius: 150px;
		transform: rotate(100deg);
	}
	100% {
		background-position: 0% 50%;
		border-radius: 75px;
		transform: rotate(00deg);
	}
}

.title::selection {
	background-color: rgba(244, 114, 182, 0.2);
	color: white;
}
</style>
