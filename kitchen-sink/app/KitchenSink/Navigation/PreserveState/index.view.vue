<script setup lang="ts">
import { RouterLink } from 'hybridly/vue'
import Card from '~/app/Components/card.vue'
import InfoText from '~/app/Components/info-text.vue'

const UButton = resolveComponent('UButton')

defineProps<{
	time: string
}>()

const input = ref('')
const checkbox = ref(false)
const color = ref('#6D537C')
const select = ref('Backlog')
const slider = ref(50)
const number = ref(5)

const state = reactive({ input, select, slider, number, checkbox, color })
</script>

<template layout>
	<div class="flex items-start gap-4">
		<!-- local state -->
		<card title="Local state">
			<template #description>
				<p>
					Navigating clears state by default, but it can be preserved using the
					<span class="text-toned">preserveState</span> option on
					<span class="text-toned">&lt;RouterLink&gt;</span> or <span class="text-toned">router.get()</span>.
				</p>
			</template>
			<div class="items-center gap-4 grid grid-cols-3">
				<!-- input -->
				<u-input placeholder="Text input..." v-model="input" />
				<!-- select -->
				<u-select v-model="select" :items="['Backlog', 'Todo', 'In progress', 'Done']" />
				<!-- number -->
				<u-input-number v-model="number" />
				<!-- select -->
				<u-slider v-model="slider" />
				<!-- color picker -->
				<u-popover>
					<u-button label="Color picker" color="neutral" variant="outline">
						<template #leading>
							<span :style="{ backgroundColor: color }" class="rounded-full size-3" />
						</template>
					</u-button>

					<template #content>
						<u-color-picker v-model="color" class="p-2" />
					</template>
				</u-popover>
				<!-- checkbox -->
				<u-checkbox label="Checkbox" v-model="checkbox" />
			</div>
			<!-- values -->
			<pre class="mt-4" v-text="JSON.stringify(state, null, 2)" />
		</card>
		<!-- server state -->
		<Card title="Server state" description="This state is fetched from the server each time you reload.">
			<info-text :date="{ timeStyle: 'medium' }" label="Date" :content="time" />
		</Card>
		<!-- actions -->
		<Card title="Requests">
			<template #description>
				<p>
					Use these buttons to send different types of requests and observe how state is preserved or not based on the
					options used.
				</p>
			</template>
			<div class="flex flex-col gap-2">
				<u-button
					variant="subtle"
					label="Use router.get()"
					color="error"
					@click="router.get(route('kitchen-sink.navigation.preserve-state.index'))"
					class="block"
				/>
				<u-button
					variant="subtle"
					label="Use router.get() with preserveState"
					color="success"
					@click="router.get(route('kitchen-sink.navigation.preserve-state.index'), { preserveState: true })"
					class="block"
				/>
				<u-button
					variant="subtle"
					color="success"
					label="Use router.reload()"
					@click="router.reload({})"
					class="block"
				/>
				<router-link
					color="error"
					variant="subtle"
					:as="UButton"
					text="Click on a <RouterLink />"
					:href="route('kitchen-sink.navigation.preserve-state.index')"
					class="block"
				/>
				<router-link
					variant="subtle"
					:as="UButton"
					color="success"
					preserve-state
					text="Click on a <RouterLink preserve-state />"
					:href="route('kitchen-sink.navigation.preserve-state.index')"
					class="block"
				/>
			</div>
		</Card>
	</div>
</template>
