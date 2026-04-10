<script setup lang="ts" generic="T extends FormatDateTimeOptions">
import { formatDateTime, type FormatDateTimeOptions, getLocalOffset, showLocalDateTimes } from '@/utils'
import { computed } from 'vue'

const $props = defineProps<{
	datetime: string
	options?: FormatDateTimeOptions
}>()

const localOffset = getLocalOffset()

const relative = computed(() =>
	$props.datetime
		? formatDateTime($props.datetime, { relative: true, ...$props.options })
		: undefined
)

const local = computed(() => {
	const defaultOptions: FormatDateTimeOptions = {
		timeStyle: $props.options?.month ? undefined : 'long',
	}

	return $props.datetime
		? formatDateTime($props.datetime, { ...defaultOptions, ...$props.options, local: true })
		: undefined
})

const utc = computed(() => {
	const defaultOptions: FormatDateTimeOptions = {
		timeStyle: $props.options?.month ? undefined : 'long',
	}

	return $props.datetime
		? formatDateTime($props.datetime, { ...defaultOptions, ...$props.options, local: false })
		: undefined
})

const formattedDate = computed(() => {
	const isLocal = $props.options?.local ?? showLocalDateTimes.value

	return {
		date: isLocal ? local.value : utc.value,
		timezone: isLocal ? localOffset : 'UTC',
	}
})
</script>

<template>
	<slot :date="formattedDate.date" :timezone="formattedDate.timezone" :options>
		<div class="text-sm">
			<div class="gap-3 grid px-3 py-4 font-mono">
				<div class="flex items-end gap-3 text-right">
					<span class="w-12 text-muted text-sm/none">UTC</span>
					<span class="text-sm/none" v-text="utc" />
				</div>
				<div class="flex items-end gap-3 text-right">
					<span class="w-12 text-muted text-sm/none">{{ localOffset }}</span>
					<span class="text-sm/none" v-text="local" />
				</div>
				<div class="flex items-end gap-3 text-right">
					<span class="inline-flex justify-end w-12 text-muted">
						<u-icon name="tabler:clock" class="translate-x-0.5" />
					</span>
					<span class="text-sm/none" v-text="relative" />
				</div>
			</div>
		</div>
	</slot>
</template>
