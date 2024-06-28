<script setup lang="ts">
defineProps<{
	impact: 'low' | 'medium' | 'high'
}>()
</script>

<template>
	<span
		class="VPBadge ml-2 mt-0.5 rounded px-2 py-1 text-sm"
		:class="{
			'bg-red-100 text-red-900 dark:bg-red-400/10 dark:text-red-300': impact === 'high',
			'bg-orange-100 text-orange-900 dark:bg-orange-400/10 dark:text-orange-300': impact === 'medium',
			'bg-gray-100 text-gray-700 dark:bg-gray-400/10 dark:text-gray-300': impact === 'low',
		}"
		:title="`This is expected to have a ${impact} impact on your project`"
		v-text="`${impact} impact`"
	/>
</template>
