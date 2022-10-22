<script setup lang="ts">
type FeatureItem = {
	title: string
	description: string
	icon: string
	url?: string
}

defineProps<{
	feature: FeatureItem
}>()
</script>

<template>
	<component
		:is="feature.url ? 'a' : 'div'"
		class="flex max-w-md flex-col gap-y-5 gap-x-8 rounded-3xl bg-[#FFF4FF] p-8 transition
        duration-300 dark:bg-[#2a2b31] sm:flex-row
        "
		:href="feature.url"
		:class="{
			'hover:bg-[#FFEBFF] dark:hover:bg-[#32323c]': feature.url,
		}"
	>
		<div class="grid shrink-0 place-items-center">
			<div
				class="grid h-14 w-14 place-items-center
                rounded-full bg-gradient-to-bl
                from-violet-300 to-pink-300 text-white dark:from-[#5F3D82] dark:to-violet-900 dark:text-pink-400
                "
			>
				<div v-html="feature.icon" />
			</div>
		</div>
		<div class="space-y-4">
			<div class="text-center text-lg font-bold dark:text-white sm:text-left">
				{{ feature.title }}
			</div>
			<div class="min-h-[6rem] font-medium opacity-50">
				{{ feature.description }}
			</div>
		</div>
	</component>
</template>
