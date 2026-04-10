<script setup lang="ts">
defineProps<{
	title: string
	description?: string
	externalReferences?: string[]
	preserveSroll?: boolean
}>()
</script>

<template>
	<u-card :ui="{ root: 'flex flex-col', body: 'grow flex flex-col overflow-hidden' }">
		<template #header>
			<div class="flex flex-col text-sm">
				<span v-text="title" />
				<p class="mt-1 text-muted" v-if="description || $slots.description">
					<slot name="description">{{ description }}</slot>
				</p>
				<div class="flex flex-col gap-1">
					<div class="flex items-center gap-x-2 mt-3" v-for="reference in externalReferences" :key="reference">
						<u-icon name="lucide:external-link" />
						<u-link :href="reference">{{ reference }}</u-link>
					</div>
				</div>
			</div>
		</template>
		<div class="overflow-auto grow" :scroll-region="preserveSroll">
			<slot />
		</div>
		<template #footer v-if="$slots.footer">
			<slot name="footer" />
		</template>
	</u-card>
</template>
