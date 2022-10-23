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
			If it's typable, it's typed. Hybridly supports typing paginators,
			routes, authorizations, and global properties.
		`,
		icon: '<div class="i-fluent-code-24-filled h-7 w-7" />',
		url: '',
	},
	{
		title: 'Powerful Forms',
		description: `
			No need to deal with handling responses and errors manually.
			Everything is already handled for you!
		`,
		icon: '<div class="i-ph-magic-wand h-6 w-6" />" />',
		url: '/guide/forms',
	},
	{
		title: 'Infinite Scrolling',
		description: `
			Having the infinite scrolling support enables for so many possibities when
			designing modern UX.
		`,
		icon: '<div class="i-ion-infinite-outline h-6 w-6" />" />',
		url: '',
	},
	{
		title: 'Typed Paginator',
		description: `
			We provide first-party support for Laravel’s paginator so you can easily work with it's object.
		`,
		icon: '<div class="i-fluent-document-page-number-24-regular h-6 w-6" />" />',
		url: '',
	},
	{
		title: 'Test Helpers',
		description: `
			To help you confidently test your Laravel application we've provided
			fluent test helpers.
		`,
		icon: '<div class="i-ph-flask-fill h-6 w-6" />" />',
		url: '/guide/testing',
	},
	{
		title: 'Route Helper',
		description: `
			All your backend routes are accessible via the route helper method.
			No more visitng your route web.
		`,
		icon: '<div class="i-ph-plugs-connected h-6 w-6" />" />',
		url: '/guide/routing',
	},
	{
		title: 'Partial Reloads',
		description: `
			You can refetch data without a hard refresh.
			Plus, all your app state will be preserved!
		`,
		icon: '<div class="i-ion-reload h-6 w-6" />" />',
		url: '/guide/partial-reloads',
	},
	{
		title: 'Persistent Data',
		description: `
			If there are data that always need to be included in every response,
			you can persist them.
		`,
		icon: '<div class="i-ph-database-duotone h-6 w-6" />" />',
		url: '/guide/persistent-properties',
	},
]
</script>

<template>
	<section class="mx-auto w-full max-w-screen-xl px-5 pt-32">
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
