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
		class="grid max-w-sm gap-3 rounded-3xl bg-[#FFF4FF] px-8 py-7 transition
        duration-300 dark:bg-[#2a2b31]
        "
		:href="feature.url"
		:class="{
			'hover:bg-[#FFEBFF] dark:hover:bg-[#32323c]': feature.url,
		}"
	>
		<div class="flex items-center justify-start gap-3">
			<div
				class="grid h-11 w-11 place-items-center
                rounded-full bg-gradient-to-bl
                from-violet-300 to-pink-300 text-white dark:from-[#5F3D82] dark:to-violet-900 dark:text-pink-400
                "
			>
				<div v-html="feature.icon" />
			</div>
			<div class="text-center text-lg font-bold dark:text-white sm:text-left">
				{{ feature.title }}
			</div>
		</div>
		<div class="min-h-[5rem] font-medium opacity-50">
			{{ feature.description }}
		</div>
	</component>
</template>
