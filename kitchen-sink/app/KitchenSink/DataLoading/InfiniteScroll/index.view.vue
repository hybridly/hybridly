<script setup lang="ts">
import { InfiniteScroll, router } from 'hybridly/vue'
import Card from '~/app/Components/card.vue'
import { formatDateTime } from '~/app/utils'

interface FeedEntry {
	id: number
	title: string
	author: string
	summary: string
	publishedAt: string
}

interface ChatMessage {
	id: number
	author: string
	content: string
	side: 'left' | 'right'
	sentAt: string
}

interface PageEnvelope<T> {
	data: T[]
}

defineProps<{
	feed: PageEnvelope<FeedEntry>
	chat: PageEnvelope<ChatMessage>
}>()

useHead({
	title: 'Infinite scroll',
})

const feedBuffer = ref(220)
const feedPreserveUrl = ref(false)
const chatBuffer = ref(120)
const chatPreserveUrl = ref(false)

function resetDemo() {
	router.external(window.location.pathname)
}
</script>

<template layout>
	<div class="gap-4 grid grid-cols-1 xl:grid-cols-2 grow">
		<card title="Automatic, bidirectional feed">
			<template #description>
				<p>
					This feed starts in the middle so both directions are available immediately.
					<code class="text-toned">&lt;InfiniteScroll&gt;</code> tracks the dominant visible page in the URL by default
					while loading more records above or below.
				</p>
			</template>

			<div class="flex justify-between items-center gap-4 mb-4 text-sm">
				<div class="flex items-center gap-4">
					<u-checkbox v-model="feedPreserveUrl" label="Preserve URL" />
					<u-form-field label="Buffer" orientation="horizontal" :hint="`${feedBuffer}px`">
						<u-slider v-model="feedBuffer" :min="0" :max="500" class="w-40" />
					</u-form-field>
				</div>
				<div class="flex items-center gap-4">
					<u-button label="Reset page state" color="neutral" variant="ghost" @click="resetDemo" />
				</div>
			</div>

			<div class="flex flex-col gap-4 p-4 border border-muted rounded-lg h-125 overflow-auto">
				<infinite-scroll data="feed" :buffer="feedBuffer" :preserve-url="feedPreserveUrl" class="flex flex-col gap-4">
					<template #previous="{ available, disabled, load, visiblePage }">
						<div class="flex justify-between items-center gap-4 pb-3 text-muted text-xs">
							<span>Visible page: {{ visiblePage?.paginator.current ?? 1 }}</span>
							<u-button v-if="available" label="Load previous page" size="xs" variant="ghost" :disabled @click="load" />
						</div>
					</template>

					<template #loading="{ direction }">
						<div class="flex justify-center py-2 text-muted text-xs">
							Loading {{ direction === 'previous' ? 'previous' : 'next' }} page...
						</div>
					</template>

					<template #default="{ page }">
						<section class="flex flex-col gap-3">
							<div class="flex justify-between items-center text-muted text-xs uppercase tracking-wide">
								<span>Page {{ page.paginator.current ?? 1 }}</span>
								<span>{{ page.property.data.length }} items</span>
							</div>
							<article
								v-for="entry in page.property.data"
								:key="entry.id"
								class="flex flex-col gap-2 bg-muted/20 p-4 border border-muted/60 rounded-lg"
							>
								<div class="flex justify-between items-center gap-4">
									<h3 class="font-medium" v-text="entry.title" />
									<span class="text-muted text-xs" v-text="formatDateTime(entry.publishedAt, { relative: true })" />
								</div>
								<p class="text-muted text-sm" v-text="entry.summary" />
								<div class="text-muted text-xs">By {{ entry.author }}</div>
							</article>
						</section>
					</template>

					<template #next="{ available, disabled, load }">
						<div class="flex justify-center pt-3">
							<u-button v-if="available" label="Load next page" size="xs" variant="ghost" :disabled @click="load" />
						</div>
					</template>
				</infinite-scroll>
			</div>
		</card>

		<card title="Manual reverse chat">
			<template #description>
				<p>
					This example uses <code class="text-toned">manual</code> and <code class="text-toned">reverse</code>
					to turn the paginator into a chat timeline: older messages load above, newer ones load below.
				</p>
			</template>

			<div class="flex justify-between items-center gap-4 mb-4 text-sm">
				<div class="flex items-center gap-4">
					<u-checkbox v-model="chatPreserveUrl" label="Preserve URL" />
					<u-form-field label="Buffer" orientation="horizontal" :hint="`${chatBuffer}px`">
						<u-slider v-model="chatBuffer" :min="0" :max="300" class="w-40" />
					</u-form-field>
				</div>
				<span class="text-muted text-xs">Manual mode keeps auto-loading disabled.</span>
			</div>

			<div class="flex flex-col gap-4 p-4 border border-muted rounded-lg h-125 overflow-auto">
				<infinite-scroll
					data="chat"
					manual
					reverse
					:buffer="chatBuffer"
					:preserve-url="chatPreserveUrl"
					class="flex flex-col gap-4"
				>
					<template #previous="{ available, disabled, load, visiblePage }">
						<div class="flex justify-between items-center gap-4 pb-3 text-muted text-xs">
							<span>Visible page: {{ visiblePage?.paginator.current ?? 1 }}</span>
							<u-button v-if="available" label="Load older messages" size="xs" :disabled @click="load" />
						</div>
					</template>

					<template #loading="{ direction }">
						<div class="flex justify-center py-2 text-muted text-xs">
							{{ direction === 'previous' ? 'Fetching older messages...' : 'Fetching newer messages...' }}
						</div>
					</template>

					<template #default="{ page }">
						<section class="flex flex-col gap-3">
							<div class="text-muted text-xs uppercase tracking-wide">
								Chunk {{ page.paginator.current ?? 1 }}
							</div>
							<div
								v-for="message in page.property.data"
								:key="message.id"
								class="flex"
								:class="message.side === 'right' ? 'justify-end' : 'justify-start'"
							>
								<div
									class="px-4 py-3 rounded-2xl max-w-[80%] text-sm"
									:class="message.side === 'right' ? 'bg-primary text-inverted' : 'bg-muted text-default'"
								>
									<div class="opacity-70 mb-1 text-[11px]">
										{{ message.author }} • {{ formatDateTime(message.sentAt, { relative: true }) }}
									</div>
									<p v-text="message.content" />
								</div>
							</div>
						</section>
					</template>

					<template #next="{ available, disabled, load }">
						<div class="flex justify-center pt-3">
							<u-button
								v-if="available"
								label="Load newer messages"
								size="xs"
								variant="ghost"
								:disabled
								@click="load"
							/>
						</div>
					</template>
				</infinite-scroll>
			</div>
		</card>
	</div>
</template>
