<script setup lang="ts">
defineProps<{
	title: string
	description?: string
	preserveSroll?: boolean
}>()
</script>

<template>
	<u-card :ui="{ root: 'flex flex-col', body: 'grow flex flex-col overflow-hidden' }">
		<!-- header -->
		<template #header>
			<div class="flex flex-col text-sm">
				<!-- title -->
				<span v-text="title" />
				<!-- optional description -->
				<p class="mt-1 text-muted" v-if="description || $slots.description">
					<slot name="description">{{ description }}</slot>
				</p>
			</div>
		</template>
		<!-- content -->
		<div class="overflow-auto grow" :scroll-region="preserveSroll">
			<slot />
		</div>
	</u-card>
</template>
