<script setup lang="ts">
import { match } from '@hybridly/utils'

const $props = defineProps<{
	status: number
}>()

const title = computed(() =>
	match($props.status, {
		404: 'Page not found.',
		403: 'Forbidden.',
		401: 'Unauthorized.',
		500: 'Oops.',
		503: 'Maintenance in progress',
		default: 'Oops.',
	})
)

const description = computed(() =>
	match($props.status, {
		503: 'Please check back soon.',
		404: 'Sorry, the page you are looking for could not be found.',
		403: 'Sorry, you are forbidden from accessing this page.',
		401: 'Sorry, you are not authorized to access this page.',
		default: 'Sorry, something happened. Try again later.',
	})
)

const canGoBackHome = computed(() => !router.matches('dashboard.index') && $props.status !== 503)

// In case of server error, the page refreshes every 2 minutes,
// so we can avoid being stuck on the oops page for a day.
if ([503, 500].includes($props.status)) {
	useIntervalFn(() => router.reload(), 60 * 1000)
}

useHead({
	title: () => title.value.replace(/\.$/, ''),
})
</script>

<template>
	<u-error
		class="w-full"
		:redirect="route('index')"
		:error="{
			statusCode: $props.status,
			statusMessage: title,
			message: description,
		}"
	>
		<template #links>
			<u-button v-if="canGoBackHome" size="lg" color="primary" label="Back to home" @click="router.to('index')" />
		</template>
	</u-error>
</template>
