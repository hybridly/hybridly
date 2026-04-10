<script setup lang="ts">
import { HybridRequestOptions } from 'hybridly'
import Card from '~/app/Components/card.vue'
import '@andypf/json-viewer'
import { match } from '@hybridly/utils'
import InfoText from '~/app/Components/info-text.vue'

defineProps<{
	time: string
}>()

type Type =
	| 'before'
	| 'data'
	| 'invalid'
	| 'error'
	| 'fail'
	| 'exception'
	| 'progress'
	| 'start'
	| 'success'
	| 'abort'
	| 'after'

interface Event {
	type: Type
	serialized: object
}

const hooks = ref<Event[]>()
const preventNonHybridModal = ref(true)

function getColorByType(type: Type) {
	return match(type, {
		before: 'text-muted',
		data: 'text-info',
		invalid: 'text-warning',
		error: 'text-error',
		fail: 'text-error',
		exception: 'text-error',
		progress: 'text-info',
		start: 'text-success',
		success: 'text-success',
		abort: 'text-warning',
		after: 'text-muted',
		default: 'text-muted',
	})
}

function sendSuccessRequest() {
	sendLifecycleRequest()
}

function sendFileUploadRequest(type: 'lightweight' | 'heavy') {
	sendLifecycleRequest({
		method: 'POST',
		data: {
			file: type === 'lightweight'
				? new File(['Hello, world!'], 'hello.txt', { type: 'text/plain' })
				: new File([new Blob(['a'.repeat(100 * 1024 * 1024)], { type: 'text/plain' })], 'heavy.txt', {
					type: 'text/plain',
				}),
		},
	})
}

function sendExceptionRequest() {
	sendLifecycleRequest({
		url: route('kitchen-sink.navigation.lifecycle.exception'),
	})
}

function sendAbortRequest(method: 'abort-controller' | 'before-hook' | 'data-hook') {
	const abortController = new AbortController()

	if (method === 'abort-controller') {
		useTimeoutFn(() => abortController.abort(), 50)
	}

	sendLifecycleRequest({
		abortController,
		hooks: {
			before() {
				if (method === 'before-hook') {
					return false
				}
			},
			data() {
				if (method === 'data-hook') {
					return false
				}
			},
		},
	})
}

function sendLifecycleRequest(options: HybridRequestOptions = {}) {
	hooks.value = []
	router.navigate({
		url: route('kitchen-sink.navigation.lifecycle.success'),
		preserveState: true,
		replace: true,
		...options,
		hooks: {
			before(request, context) {
				hooks.value?.push({
					type: 'before',
					serialized: { request },
				})

				return options?.hooks?.before?.(request, context)
			},
			data(request, response, context) {
				hooks.value?.push({
					type: 'data',
					serialized: { request, response },
				})

				return options?.hooks?.data?.(request, response, context)
			},
			invalid(request, response) {
				hooks.value?.push({
					type: 'invalid',
					serialized: { request, response },
				})

				if (preventNonHybridModal.value === true) {
					return false
				}
			},
			error(errors, request) {
				hooks.value?.push({
					type: 'error',
					serialized: { errors, request },
				})
			},
			fail(error, request) {
				hooks.value?.push({
					type: 'fail',
					serialized: { error, request },
				})
			},
			exception(error, request) {
				hooks.value?.push({
					type: 'exception',
					serialized: { error, request },
				})
			},
			progress(progress, request) {
				hooks.value?.push({
					type: 'progress',
					serialized: { progress, request },
				})
			},
			start(request) {
				hooks.value?.push({
					type: 'start',
					serialized: { request },
				})
			},
			success(payload, request, response) {
				hooks.value?.push({
					type: 'success',
					serialized: { payload, request, response },
				})
			},
			abort(request) {
				hooks.value?.push({
					type: 'abort',
					serialized: { request },
				})
			},
			after(request) {
				hooks.value?.push({
					type: 'after',
					serialized: { request },
				})
			},
		},
	})
}

const theme = {
	base00: 'transparent',
	base01: '#282828',
	base02: '#383838',
	base03: '#585858',
	base04: '#b8b8b8',
	base05: '#d8d8d8',
	base06: '#e8e8e8',
	base07: '#f8f8f8',
	base08: '#ab4642',
	base09: '#dc9656',
	base0A: '#f7ca88',
	base0B: '#a1b56c',
	base0C: '#86c1b9',
	base0D: '#7cafc2',
	base0E: '#ba8baf',
	base0F: '#a16946',
}
</script>

<template layout>
	<div class="flex gap-4 overflow-hidden grow">
		<card title="Request log" class="w-full">
			<template #description>
				<p>
					Send requests and inspect the logs to observe how requests behave.
				</p>
			</template>
			<!-- value -->
			<div class="flex flex-col">
				<u-accordion :items="hooks">
					<template #leading="{ item }">
						<span :class="getColorByType(item.type)" v-text="item.type" />
					</template>
					<template #content="{ item, open }">
						<andypf-json-viewer
							v-if="open"
							show-data-types="false"
							show-copy="false"
							show-size="false"
							show-toolbar="false"
							expand-icon-type="square"
							expanded="2"
							:theme="JSON.stringify(theme)"
						>
							{{ item.serialized }}
						</andypf-json-viewer>
					</template>
				</u-accordion>
			</div>
		</card>
		<div class="flex flex-col gap-4">
			<!-- server state -->
			<Card title="Server state" description="This state is fetched from the server each time you reload.">
				<info-text :date="{ timeStyle: 'medium' }" label="Date" :content="time" />
			</Card>
			<!-- actions -->
			<Card title="Requests">
				<template #description>
					<p>
						Use these buttons to send different types of requests and observe the lifecycle hooks being triggered in the
						log.
					</p>
					<p class="mt-2">
						To observe an <span class="text-error">exception</span> event, you can open the
						<span class="text-highlighted">DevTools</span> and set the throttling to
						<span class="text-highlighted">Offline</span> in the <span class="text-highlighted">Network</span> tab.
					</p>
				</template>
				<div class="flex flex-col gap-2">
					<u-button
						variant="subtle"
						label="Successful request"
						color="success"
						@click="sendSuccessRequest()"
						class="block"
					/>
					<u-button
						variant="subtle"
						label="Successful file upload request"
						color="success"
						@click="sendFileUploadRequest('lightweight')"
						class="block"
					/>
					<u-button
						variant="subtle"
						label="Successful large file upload request"
						color="success"
						@click="sendFileUploadRequest('heavy')"
						class="block"
					/>
					<u-button
						variant="subtle"
						label="Aborted via AbortController"
						color="warning"
						@click="sendAbortRequest('abort-controller')"
						class="block"
					/>
					<u-button
						variant="subtle"
						label="Prevented via before hook"
						color="warning"
						@click="sendAbortRequest('before-hook')"
						class="block"
					/>
					<u-button
						variant="subtle"
						label="Prevented via data hook"
						color="warning"
						@click="sendAbortRequest('data-hook')"
						class="block"
					/>
					<u-button
						variant="subtle"
						label="Invalid (eg. HTTP 500)"
						color="error"
						@click="sendExceptionRequest()"
						class="block"
					/>
					<u-checkbox class="mt-2" v-model="preventNonHybridModal" label="Prevent exception modal">
						<template #description>
							When checked, the <span class="text-toned">exception</span> hook will return false, preventing the default
							behavior of showing a modal when an exception occurs.
						</template>
					</u-checkbox>
				</div>
			</Card>
		</div>
	</div>
</template>
