<script setup lang="ts">
import { Deferred as HDeferred } from 'hybridly/vue'
import Card from '~/app/Components/card.vue'

const $props = defineProps<{
	instant: string[]
	deferred?: string[]
	grouped?: string[]
}>()

useHead({
	title: 'Deferred properties',
})

// this is just for typescript
const properties = computed(() => $props as Record<string, string[]>)

const blocks = [
	{
		title: 'Instant property',
		description: 'This property was loaded instantly with the page.',
		data: 'instant',
	},
	{
		title: 'Deferred property',
		description: 'This property was loaded *after* the page was rendered.',
		data: 'deferred',
	},
	{
		title: 'Deferred property with named group',
		description: 'This deferred property is fetched in another request.',
		data: 'grouped',
	},
]
</script>

<template layout>
	<div class="gap-4 grid grid-cols-1 2xl:grid-cols-3 xl:grid-cols-2">
		<template v-for="block in blocks" :key="block.data">
			<!-- using the <Deferred> headless component -->
			<h-deferred :data="block.data" #default="{ reloading, loading, loaded }">
				<card :title="block.title" :description="block.description">
					<!-- if loaded, display values -->
					<div class="mb-2" v-if="loaded">
						<div v-for="property in properties[block.data]" class="flex items-baseline gap-2">
							<u-icon name="lucide:arrow-right" class="size-3 shrink-0" />
							<span>{{ property }}</span>
						</div>
					</div>
					<!-- footer -->
					<template #footer>
						<div class="flex justify-between items-center">
							<!-- state -->
							<div class="flex gap-6 text-muted text-sm">
								<span>Loading: {{ loading ? 'yes' : 'no' }}</span>
								<span>Reloading: {{ reloading ? 'yes' : 'no' }}</span>
								<span>Loaded: {{ loaded ? 'yes' : 'no' }}</span>
							</div>
							<!-- actions -->
							<u-button label="Reload" :loading @click="router.reload({ only: block.data })" />
						</div>
					</template>
				</card>
			</h-deferred>
		</template>
		<!-- reloading -->
		<Teleport defer to="#header-actions">
			<u-button label="Full reload" @click="router.external(route('kitchen-sink.data-loading.deferred.index'))" />
			<u-button label="Partial reload" @click="router.reload({ except: [] })" />
		</Teleport>
	</div>
</template>
