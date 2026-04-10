<script setup lang="ts">
import { defineInfoList } from './use-info-list'

const $props = defineProps<{
	direction?: 'vertical' | 'horizontal'
}>()

defineInfoList({
	direction: $props.direction ?? 'horizontal',
})
</script>

<template>
	<div class="flex flex-col gap-y-1">
		<slot />
	</div>
</template>
