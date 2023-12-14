<script setup lang="ts">
defineProps<{
	impact: 'low' | 'medium' | 'high'
}>()
</script>

<template>
	<span
		class="VPBadge text-sm px-2 rounded py-1 ml-2 mt-0.5"
		:class="{
			'bg-red-100 dark:bg-red-400/10 text-red-900 dark:text-red-300': impact === 'high',
			'bg-orange-100 dark:bg-orange-400/10 text-orange-900 dark:text-orange-300': impact === 'medium',
			'bg-gray-100 dark:bg-gray-400/10 text-gray-700 dark:text-gray-300': impact === 'low',
		}"
		:title="`This is expected to have a ${impact} impact on your project`"
		v-text="impact + ' impact'"
	/>
</template>
