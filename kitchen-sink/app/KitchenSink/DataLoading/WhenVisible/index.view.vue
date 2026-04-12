<script setup lang="ts">
import { router, WhenVisible } from 'hybridly/vue'
import { ref } from 'vue'
import Card from '~/app/Components/card.vue'

const $props = defineProps<{
	receivedAt?: string
}>()

useHead({
	title: 'When visible',
})

const fallbackOnHidden = ref(false)
const once = ref(true)
const buffer = ref(100)
const loadingEvents = ref(0)

function onLoading() {
	loadingEvents.value += 1
}

function reset() {
	router.reload({
		mode: 'navigation',
		preserveState: false,
		preserveScroll: false,
		replace: false,
		interruptAsyncOnStart: 'all',
	})
}
</script>

<template layout>
	<div class="gap-4 grid grid-cols-1 xl:grid-cols-2">
		<card title="Loading when visible">
			<template #description>
				<p>
					This example uses <span class="text-toned">&lt;WhenVisible&gt;</span> to request an
					<span class="text-toned">on_demand</span> property only when the sentinel becomes visible.
				</p>
			</template>

			<!-- controls -->
			<div class="flex justify-between items-center gap-4 mb-4 text-sm">
				<!-- options -->
				<div class="flex items-center gap-4">
					<u-checkbox v-model="fallbackOnHidden" label="Show fallback when hidden" />
					<u-checkbox v-model="once" label="Load once" />
					<u-form-field label="Buffer" orientation="horizontal" :hint="`${buffer}px`">
						<u-slider v-model="buffer" :min="0" :max="1500" class="w-52" />
					</u-form-field>
				</div>
				<!-- actions -->
				<div class="flex items-center gap-4">
					<u-button label="Load now" @click="router.reload({ only: ['receivedAt'] })" />
					<u-button label="Reset" color="neutral" variant="ghost" @click="reset" />
				</div>
			</div>
			<!-- debug -->
			<div class="flex items-center gap-x-4 mb-4">
				<span class="text-muted">Loading events: {{ loadingEvents }}</span>
				<span class="text-muted">Data loaded: {{ receivedAt ? 'yes' : 'no' }}</span>
			</div>
			<!-- example -->
			<div id="when-visible-scroll-root" class="flex flex-col p-4 border border-muted rounded-lg h-80 overflow-auto">
				<!-- help -->
				<div class="flex flex-col justify-between items-center gap-3 text-muted text-sm">
					<span>Scroll to the bottom of this panel to trigger a partial reload.</span>
					<u-icon name="lucide:arrow-down" class="animate-bounce" />
				</div>
				<!-- spacer -->
				<div class="h-[150%] shrink-0" />
				<!-- revealed when visible -->
				<when-visible
					data="receivedAt"
					:buffer
					root="#when-visible-scroll-root"
					:once
					:fallback-on-hidden="fallbackOnHidden"
					@loading="onLoading"
					class="relative flex items-center p-4 border border-muted/20 rounded-md"
				>
					<!-- actual content -->
					<template #default="{ loading, reloading, loaded }">
						<div class="flex flex-col justify-center items-center gap-2 w-full">
							<p>Loaded.</p>
							<p class="text-muted text-xs">
								Loading: {{ loading ? 'yes' : 'no' }}
								| Reloading: {{ reloading ? 'yes' : 'no' }}
								| Loaded: {{ loaded ? 'yes' : 'no' }}
								| Received at: {{ receivedAt }}
							</p>
						</div>
					</template>

					<template #fallback="{ loading, reloading, loaded }">
						<div class="flex flex-col justify-center items-center gap-2 w-full">
							<p>Waiting for data to load...</p>
							<p class="text-muted text-xs">
								Loading: {{ loading ? 'yes' : 'no' }}
								| Reloading: {{ reloading ? 'yes' : 'no' }}
								| Loaded: {{ loaded ? 'yes' : 'no' }}
								| Received at: {{ receivedAt }}
							</p>
						</div>
					</template>
				</when-visible>
			</div>
		</card>
	</div>
</template>
