<script setup lang="ts">
import { tv } from '@nuxt/ui/runtime/utils/tv.js'
import { useDialog } from 'hybridly/vue'

const { show, closeLocally, unmount } = useDialog()

defineProps<{
	size?: 'md' | 'xl' | 'fit'
}>()

const modal = tv({
	slots: {
		content: '',
		header: '',
	},
	variants: {
		size: {
			md: {
				content: 'max-w-xl',
				header: 'p-4',
			},
			xl: {
				content: 'max-w-6xl',
				header: 'p-4',
			},
			fit: {
				content: 'max-w-max',
				header: 'p-4',
			},
		},
	},
	defaultVariants: {
		size: 'md',
	},
})
</script>

<template>
	<u-modal
		:open="show"
		@update:open="(value) => !value && closeLocally()"
		@after:leave="unmount"
		:ui="{
			content: modal().content({ size }),
			header: modal().header({ size }),
			close: 'static',
			footer: 'justify-end',
		}"
		:close="false"
	>
		<template #title v-if="$slots.title">
			<slot name="title" :close="closeLocally" />
		</template>
		<template #header v-if="$slots.header">
			<slot name="header" :close="closeLocally" />
		</template>
		<template #body v-if="$slots.default">
			<slot :close="closeLocally" />
		</template>
		<template #content v-if="$slots.content">
			<slot name="content" :close="closeLocally" />
		</template>
		<template #footer v-if="$slots.footer">
			<slot name="footer" :close="closeLocally" />
		</template>
	</u-modal>
</template>
