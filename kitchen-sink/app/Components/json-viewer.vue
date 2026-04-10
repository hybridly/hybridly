<script setup lang="ts">
import '@andypf/json-viewer'

const props = defineProps<{
	data: string | object
	expanded?: number
}>()

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

const viewer = useTemplateRef('viewer')

onMounted(() => {
	watch(() => props.data, (data) => {
		if (viewer.value) {
			viewer.value.data = JSON.stringify(data)
		}
	}, { deep: true, immediate: true })
})
</script>

<template>
	<andypf-json-viewer
		ref="viewer"
		show-data-types="false"
		show-copy="false"
		show-size="false"
		show-toolbar="false"
		expand-icon-type="square"
		:expanded="expanded ?? 2"
		:theme="JSON.stringify(theme)"
	/>
</template>
