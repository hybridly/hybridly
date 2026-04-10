<script setup lang="ts" generic="T">
import { formatDateTime, FormatDateTimeOptions } from '../utils'
import UseDateTime from './use-date-time.vue'
import { useInfoList } from './use-info-list'

const $props = defineProps<{
	label: string
	content: T
	date?: boolean | FormatDateTimeOptions
	extractText?: (item: T) => string
}>()

const { direction } = useInfoList()

/**
 * Extracts text from the item
 */
function extractTextFromContent(item: T): undefined | string {
	const text = $props.extractText?.(item)
	if (text) {
		return text
	}

	if (typeof $props.content === 'number') {
		return $props.content.toString()
	}

	if (typeof $props.content === 'string') {
		return $props.content
	}

	return undefined
}
</script>

<template>
	<div
		class="flex"
		:class="{
			'flex-col gap-y-2': direction === 'vertical',
			'flex-row items-center gap-x-2': direction === 'horizontal' || !direction,
		}"
	>
		<span class="text-sm uppercase" v-text="label" />
		<span class="text-muted">
			<template v-if="$slots.default">
				<slot :text="extractTextFromContent(content)" :content />
			</template>
			<template v-else-if="date && extractTextFromContent(content)">
				<u-popover mode="hover" :open-delay="250">
					<span
						class="decoration-dashed decoration-neutral-700 underline underline-offset-5"
						v-text="formatDateTime(extractTextFromContent(content)!, typeof date === 'object' ? date : {})"
					/>
					<template #content>
						<use-date-time :datetime="extractTextFromContent(content)!" />
					</template>
				</u-popover>
			</template>
			<span v-else class="truncate" v-text="extractTextFromContent(content)" />
		</span>
	</div>
</template>
