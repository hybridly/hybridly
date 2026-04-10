<script setup lang="ts">
import { HybridRequestOptions } from 'hybridly'
import Card from '~/app/Components/card.vue'

defineProps<{
	time: string
}>()

interface EventLog {
	id: number
	time: Date
	text: string
	type: 'neutral' | 'success' | 'error'
}

const counter = ref(0)
const responseDelay = ref(2)
const log = ref<EventLog[]>([])
const controllers = ref<Map<number, AbortController>>(new Map())

function uniqueColorById(id: number) {
	const hue = (id * 137.508) % 360 // use golden angle approximation for even distribution
	return `oklch(0.7 0.15 ${hue})`
}

const canCancel = computed(() => controllers.value.size > 0)

function clearLog() {
	log.value = []
}

function cancelFirstRequest() {
	if (controllers.value.size === 0) {
		return
	}

	const id = controllers.value.keys().next().value!
	controllers.value.get(id)?.abort()
	controllers.value.delete(id)
}

function sendLoggableRequest(mode: 'navigation' | 'async', options: HybridRequestOptions = {}) {
	const id = counter.value++
	const abortController = new AbortController()
	controllers.value.set(id, abortController)

	router.get(route('kitchen-sink.navigation.async-requests.delay'), {
		data: { delay: responseDelay.value },
		mode,
		abortController,
		preserveState: true,
		...options,
		hooks: {
			after() {
				controllers.value.delete(id)
			},
			success() {
				log.value.push({
					id,
					time: new Date(),
					text: `${mode} request succeeded`,
					type: 'success',
				})
			},
			abort() {
				log.value.push({
					id,
					time: new Date(),
					text: `${mode} request aborted`,
					type: 'error',
				})
			},
		},
	})

	useTimeoutFn(() => {
		log.value.push({
			id,
			time: new Date(),
			text: `sending ${mode} request...`,
			type: 'neutral',
		})
	}, 1)
}
</script>

<template layout>
	<div class="gap-4 grid grid-cols-2 grow">
		<card title="Request log" class="h-full">
			<template #description>
				<p>
					Send requests and inspect the logs to observe how requests behave.
				</p>
			</template>
			<!-- values -->
			<div class="flex flex-col overflow-scroll">
				<span
					v-for="item in log"
					:key="item.id"
					class="text-sm"
					:class="{
						'text-success': item.type === 'success',
						'text-error': item.type === 'error',
						'text-muted': item.type === 'neutral',
					}"
				>
					[{{ item.time.toLocaleTimeString() }}] <span
						class="font-medium"
						:style="{ color: uniqueColorById(item.id) }"
					>#{{ item.id }}</span> {{ item.text }}
				</span>
			</div>
		</card>
		<div class="flex flex-col justify-between gap-4">
			<!-- server state -->
			<Card title="Server state" description="This state is fetched from the server each time you reload.">
				{{ time }}
			</Card>
			<!-- actions -->
			<div class="flex flex-col gap-2">
				<span class="mt-3 mb-1 text-muted text-sm">Normal behavior</span>
				<div class="gap-2 grid grid-cols-2">
					<u-button
						variant="subtle"
						label="Navigation request"
						color="warning"
						@click="sendLoggableRequest('navigation')"
						class="block"
					/>
					<u-button
						variant="subtle"
						label="Asynchronous request"
						color="neutral"
						@click="sendLoggableRequest('async')"
						class="block"
					/>
				</div>
				<span class="mt-3 mb-1 text-muted text-sm">Interrupts all asynchronous requests</span>
				<div class="gap-2 grid grid-cols-2">
					<u-button
						variant="subtle"
						label="Navigation request"
						color="warning"
						@click="sendLoggableRequest('navigation', { interruptAsyncOnStart: 'all' })"
						class="block"
					/>
					<u-button
						variant="subtle"
						label="Asynchronous request"
						color="neutral"
						@click="sendLoggableRequest('async', { interruptAsyncOnStart: 'all' })"
						class="block"
					/>
				</div>
				<template v-for="group in ['A', 'B']" :key="group">
					<span class="mt-3 mb-1 text-muted text-sm">Group {{ group }}</span>
					<div class="gap-2 grid grid-cols-1">
						<u-button
							variant="subtle"
							label="Asynchronous request"
							color="neutral"
							@click="sendLoggableRequest('async', { group })"
							class="block"
						/>
						<u-button
							variant="subtle"
							label="Asynchronous request (interrupts same group)"
							color="neutral"
							@click="sendLoggableRequest('async', { group, interruptAsyncOnStart: 'same-group' })"
							class="block"
						/>
						<u-button
							variant="subtle"
							label="Asynchronous request (interrupts all)"
							color="neutral"
							@click="sendLoggableRequest('async', { group, interruptAsyncOnStart: 'all' })"
							class="block"
						/>
					</div>
				</template>
				<span class="mt-3 mb-1 text-muted text-sm">Self-cancels on next navigation</span>
				<div class="gap-2 grid grid-cols-2">
					<u-button
						variant="subtle"
						label="Asynchronous request"
						color="neutral"
						@click="sendLoggableRequest('async', { cancelOnNavigation: true })"
						class="block"
					/>
				</div>
				<span class="mt-3 mb-1 text-muted text-sm">Controls</span>
				<u-form-field label="Response delay" :hint="`${responseDelay}s`">
					<u-slider v-model="responseDelay" :min="0" :max="10" />
				</u-form-field>
				<div class="gap-2 grid grid-cols-2 mt-2">
					<u-button variant="subtle" label="Clear log" color="neutral" @click="clearLog" class="block" />
					<u-button
						variant="subtle"
						label="Cancel first request"
						color="error"
						@click="cancelFirstRequest"
						class="block"
						:disabled="!canCancel"
					/>
				</div>
			</div>
		</div>
	</div>
</template>
