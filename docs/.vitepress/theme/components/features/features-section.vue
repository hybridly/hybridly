<script setup lang="ts">
import Feature from './feature.vue'

type FeatureItem = {
	title: string
	description: string
	icon: string
	url?: string
}

const features: FeatureItem[] = [
	{
		title: 'FULL IDE Support',
		description: `
			Every object you interact in Vue will have auto completion and type check.
			Typescript will basically prevent you from making errors.
		`,
		icon: '<div class="i-fluent-code-24-filled h-9 w-9" />',
		url: '',
	},
	{
		title: 'Powerful Forms',
		description: `
			With the form helper you don’t need to deal with handling responses
			and errors like a traditional SPA. Everything is already handled for you!
		`,
		icon: '<div class="i-ph-magic-wand h-8 w-8" />" />',
		url: '/guide/forms',
	},
	{
		title: 'Infinite Scrolling Support',
		description: `
			Having the infinite scrolling support enables for so many possibities when
			designing modern UX.
		`,
		icon: '<div class="i-ion-infinite-outline h-8 w-8" />" />',
		url: '',
	},
	{
		title: 'Typed Paginator',
		description: `
			You no longer need to remember how Laravel’s paginator object look like.
			We provide first-party support for it so you can easily wrap your properties around it.
		`,
		icon: '<div class="i-fluent-document-page-number-24-regular h-8 w-8" />" />',
		url: '',
	},
	{
		title: 'Test Helpers',
		description: `
			To help you confidently test your Laravel application we have provided
			fluent test helpers so your pages can easily be battle tested.
		`,
		icon: '<div class="i-ph-flask-fill h-8 w-8" />" />',
		url: '/guide/testing',
	},
	{
		title: 'Routes Auto-Completion',
		description: `
			All your backend routes are accessible via the route helper method.
			This allows you to not deal with urls manually and because they’re
			typed you will not make mistakes.
		`,
		icon: '<div class="i-ph-plugs-connected h-8 w-8" />" />',
		url: '/guide/routing',
	},
	{
		title: 'Partial Reloads',
		description: `
			If you need to reload a specific set of data without doing a hard refresh,
			you can easily do that by simply calling a method.
			All your app state will be preserved!
		`,
		icon: '<div class="i-ion-reload h-8 w-8" />" />',
		url: '/guide/partial-reloads',
	},
	{
		title: 'Persistent Data',
		description: `
			If there are data that always need to be included in every response,
			you can persist them.
		`,
		icon: '<div class="i-ph-database-duotone h-8 w-8" />" />',
		url: '/guide/persistent-properties',
	},
]
</script>

<template>
	<section class="mx-auto w-full max-w-screen-2xl px-3 pt-32">
		<div class="text-center">
			<div class="text-5xl">
				<span class="font-extrabold">
					DX Feature
				</span>
				<span>
					Frenzy!
				</span>
			</div>
			<div class="mx-auto max-w-3xl pt-5 text-2xl text-[#8A92A6]">
				Out of the box we’ve included so many features and helpers that
				it will make your life so much easier as a web developer.
			</div>
		</div>
		<div class="flex flex-wrap justify-center gap-10 pt-14">
			<Feature
				v-for="feature in features"
				:key="feature.title"
				:feature="feature"
			/>
		</div>
	</section>
</template>
