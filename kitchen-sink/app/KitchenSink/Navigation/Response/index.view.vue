<script setup lang="ts">
import { HybridRequestOptions } from 'hybridly'
import Card from '~/app/Components/card.vue'

const props = defineProps<{
	time: string
	propertiesUpdateCount: number
	propertiesUpdateMethod: 'POST' | 'PUT' | null
	propertiesUpdatedAt: string | null
}>()

useHead({
	title: 'Response',
})

function sendRedirectBackRequest() {
	return router.post(route('kitchen-sink.navigation.response.redirect-back'))
}

function sendExternalRedirectRequest() {
	return router.post(route('kitchen-sink.navigation.response.redirect-external'))
}

function sendInternalRedirectRequest() {
	return router.post(route('kitchen-sink.navigation.response.redirect-internal'))
}

function sendFileDownloadRequest() {
	return router.post(route('kitchen-sink.navigation.response.download'))
}

function sendVersionChangedResponseRequest(options: HybridRequestOptions) {
	return router.get(route('kitchen-sink.navigation.response.version-changed'), options)
}

function sendPropertiesUpdateRequest(method: 'POST' | 'PUT') {
	if (method === 'POST') {
		return router.post(route('kitchen-sink.navigation.response.properties.post'), {
			preserveState: true,
			data: {
				properties_update_count: props.propertiesUpdateCount,
			},
		})
	}

	return router.put(route('kitchen-sink.navigation.response.properties.put'), {
		preserveState: true,
		data: {
			properties_update_count: props.propertiesUpdateCount,
		},
	})
}
</script>

<template layout>
	<div class="gap-4 grid grid-cols-2">
		<card
			title="Response actions"
			description="Use these actions to test the different response types supported by Hybridly."
		>
			<div class="flex flex-col gap-2">
				<u-button
					variant="subtle"
					label="Redirect back"
					color="neutral"
					@click="sendRedirectBackRequest()"
					class="block"
				/>
				<u-button
					variant="subtle"
					label="Internal redirect"
					color="neutral"
					@click="sendInternalRedirectRequest()"
					class="block"
				/>
				<u-button
					variant="subtle"
					label="File download"
					color="primary"
					@click="sendFileDownloadRequest()"
					class="block"
				/>
				<u-button
					variant="subtle"
					label="External redirect"
					color="warning"
					@click="sendExternalRedirectRequest()"
					class="block"
				/>
				<u-button
					variant="subtle"
					label="Version changed response (without preserveState)"
					color="warning"
					@click="sendVersionChangedResponseRequest({ preserveState: false })"
					class="block"
				/>
				<u-button
					variant="subtle"
					label="Version changed response (with preserveState)"
					color="warning"
					@click="sendVersionChangedResponseRequest({ preserveState: false })"
					class="block"
				/>
				<u-button
					variant="subtle"
					label="Property update after POST"
					color="info"
					@click="sendPropertiesUpdateRequest('POST')"
					class="block"
				/>
				<u-button
					variant="subtle"
					label="Property update after PUT"
					color="info"
					@click="sendPropertiesUpdateRequest('PUT')"
					class="block"
				/>
			</div>
		</card>
		<div class="flex flex-col justify-between gap-4">
			<card title="Server state" description="This state is fetched from the server each time you reload.">
				<p>
					<strong>Time:</strong>
					{{ props.time }}
				</p>
				<p>
					<strong>Update count:</strong>
					{{ props.propertiesUpdateCount }}
				</p>
				<p>
					<strong>Last method:</strong>
					{{ props.propertiesUpdateMethod ?? 'Not updated yet' }}
				</p>
				<p>
					<strong>Last updated at:</strong>
					{{ props.propertiesUpdatedAt ?? 'Not updated yet' }}
				</p>
			</card>
		</div>
	</div>
</template>
