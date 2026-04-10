<script setup lang="ts">
import { RouterLink } from 'hybridly/vue'
import Card from '~/app/Components/card.vue'
import { formatTime } from '~/app/utils'

const UButton = resolveComponent('UButton')

defineProps<{
	time: string
}>()

const messages = Array.from({ length: 100 }, (_, i) => ({
	id: i + 1,
	content: `Message ${i + 1}`,
	sent_at: new Date(Date.now() - i * 60000).toISOString(),
}))

function scrollSlightly() {
	window.scrollBy({ top: 150, behavior: 'smooth' })
}

onMounted(() => {
	document.getElementById('root')!.classList.add('scrollable')
})

onUnmounted(() => {
	document.getElementById('root')!.classList.remove('scrollable')
})
</script>

<template layout>
	<!-- playground -->
	<div class="gap-4 grid grid-cols-2">
		<card title="Scroll container" class="h-96" preserve-sroll>
			<template #description>
				<p>
					Navigating clears scrolling state by default, but it can be preserved using the
					<span class="text-toned">preserveScroll</span> option on
					<span class="text-toned">&lt;RouterLink&gt;</span> or <span class="text-toned">router.get()</span>.
				</p>
			</template>
			<div class="mb-2">
				<u-scroll-area
					#default="{ item }"
					:items="messages"
					class="flex -mr-2 pr-2 w-full"
					:ui="{ viewport: 'gap-y-2' }"
				>
					<div class="flex gap-x-4 text-sm">
						<div class="w-18 text-muted shrink-0">
							{{ formatTime(item.sent_at) }}
						</div>
						<div v-text="item.content" class="grow" />
					</div>
				</u-scroll-area>
			</div>
		</card>
		<div class="flex flex-col justify-between gap-4">
			<!-- server state -->
			<Card title="Server state" description="This state is fetched from the server each time you reload.">
				{{ time }}
			</Card>
			<!-- actions -->
			<div class="flex flex-col gap-2">
				<u-button
					variant="subtle"
					label="Use router.get()"
					color="error"
					@click="router.get(route('kitchen-sink.navigation.preserve-scroll.index'))"
					class="block"
				/>
				<u-button
					variant="subtle"
					label="Use router.get() with preserveScroll"
					color="success"
					@click="router.get(route('kitchen-sink.navigation.preserve-scroll.index'), { preserveScroll: true })"
					class="block"
				/>
				<u-button
					variant="subtle"
					color="success"
					label="Use router.reload()"
					@click="router.reload({})"
					class="block"
				/>
				<router-link
					color="error"
					variant="subtle"
					:as="UButton"
					text="Click on a <RouterLink />"
					:href="route('kitchen-sink.navigation.preserve-scroll.index')"
					class="block"
				/>
				<router-link
					variant="subtle"
					:as="UButton"
					color="success"
					preserve-scroll
					text="Click on a <RouterLink preserve-scroll />"
					:href="route('kitchen-sink.navigation.preserve-scroll.index')"
					class="block"
				/>
			</div>
		</div>
	</div>
	<!-- artificial scrolling -->
	<div class="flex flex-col items-center gap-4 my-50">
		<p>Scroll down slightly and press one of the buttons to see how it affects the scroll.</p>
		<u-button class="flex justify-center items-center size-10" @click="scrollSlightly">
			<u-icon name="lucide:arrow-down" class="animate-bounce" />
		</u-button>
	</div>
</template>

<style>
.scrollable {
	overflow: visible !important;
	min-height: 125svh;
}
</style>
