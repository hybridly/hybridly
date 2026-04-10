<script setup lang="ts">
import { router } from 'hybridly/vue'
import Card from '~/app/Components/card.vue'
import { formatDateTime } from '~/app/utils'

defineProps<{
	append: App.KitchenSink.DataLoading.Mergeable.Message[]
	prepend: App.KitchenSink.DataLoading.Mergeable.LogEntry[]
}>()

useHead({
	title: 'Mergeable properties',
})
</script>

<template layout>
	<div class="gap-4 grid grid-cols-1 xl:grid-cols-2 overflow-hidden grow">
		<!-- append -->
		<card title="Appending data">
			<template #description>
				<p>
					Using the <span class="text-toned">merge()</span> function, new data will be appended to the existing data
					instead of replacing it. This example also uses <span class="text-toned">uniqueBy: 'id'</span>, which ensures
					that all messages are de-duplicated.
				</p>
			</template>
			<div class="mb-2">
				<u-scroll-area #default="{ item }" :items="append" class="-mr-2 pr-2 w-full">
					<u-chat-message
						compact
						variant="soft"
						:icon="item.side === 'left' ? 'lucide:bot' : 'lucide:user'"
						:side="item.side"
						role="system"
						:parts="[{ type: 'text', id: item.id, text: item.content }]"
						:id="item.id"
					>
						<template #content>
							<div class="flex flex-col gap-y-1">
								<p v-text="item.content" />
								<div class="flex justify-between items-center text-muted text-xs">
									<span v-text="item.id" />
									<span v-text="formatDateTime(item.sent_at)" />
								</div>
							</div>
						</template>
					</u-chat-message>
				</u-scroll-area>
			</div>
			<!-- footer -->
			<template #footer>
				<div class="flex justify-between items-center gap-4">
					<!-- state -->
					<div class="flex gap-6 text-muted text-sm">
						<span>Messages: {{ append.length }}</span>
					</div>
					<!-- actions -->
					<div class="flex items-center gap-4">
						<u-button label="Partial reload" @click="router.reload({ only: ['append'] })" />
						<u-button label="Reset" @click="router.reload({ reset: ['append'], only: ['append'] })" />
					</div>
				</div>
			</template>
		</card>
		<!-- prepend -->
		<card title="Prepending data">
			<template #description>
				<p>
					The <span class="text-toned">merge()</span> function has a <span class="text-toned">prepend</span> argument
					which, if set to <span class="text-toned">true</span>, will prepend the new data instead of appending it.
				</p>
			</template>
			<div class="mb-2">
				<u-scroll-area
					#default="{ item }"
					:items="prepend"
					class="flex -mr-2 pr-2 w-full"
					:ui="{ viewport: 'gap-y-2' }"
				>
					<div class="flex gap-x-4 text-sm">
						<div class="w-18 text-muted shrink-0">
							{{ formatDateTime(item.sent_at, { timeStyle: 'medium', withoutDate: true }) }}
						</div>
						<div v-text="item.content" class="grow" />
					</div>
				</u-scroll-area>
			</div>
			<!-- footer -->
			<template #footer>
				<div class="flex justify-between items-center gap-4">
					<!-- state -->
					<div class="flex gap-6 text-muted text-sm">
						<span>Messages: {{ prepend.length }}</span>
					</div>
					<!-- actions -->
					<div class="flex items-center gap-4">
						<u-button label="Partial reload" @click="router.reload({ only: ['prepend'] })" />
						<u-button label="Reset" @click="router.reload({ reset: ['prepend'], only: ['prepend'] })" />
					</div>
				</div>
			</template>
		</card>
		<!-- reloading -->
		<Teleport defer to="#header-actions">
			<u-button label="Full reload" @click="router.external('')" />
			<u-button label="Partial reload" @click="router.reload({ except: [] })" />
		</Teleport>
	</div>
</template>
