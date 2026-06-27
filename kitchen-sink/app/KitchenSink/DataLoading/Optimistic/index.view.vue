<script setup lang="ts">
import Card from '~/app/Components/card.vue'

type Character = App.KitchenSink.DataLoading.Optimistic.Character

const props = defineProps<{
	characters: Character[]
}>()

useHead({
	title: 'Optimistic responses',
})

const simulateServerError = ref(false)
const processing = ref<number>()
const lastResult = ref<'success' | 'error'>()

function toggleCharacterLike(character: Character) {
	if (processing.value === character.id) {
		return
	}

	const liked = !character.liked

	processing.value = character.id
	lastResult.value = undefined

	return router.post(route('kitchen-sink.data-loading.optimistic.like'), {
		data: {
			id: character.id,
			liked,
			fail: simulateServerError.value,
		},
		updateImmediately: (properties) => ({
			characters: (properties.characters as Character[]).map((current) =>
				current.id === character.id
					? {
						...current,
						liked,
						likes: current.likes + (liked ? 1 : -1),
					}
					: current
			),
		}),
		hooks: {
			success: () => {
				lastResult.value = 'success'
			},
			fail: () => {
				lastResult.value = 'error'
			},
			'validation-error': () => {
				lastResult.value = 'error'
			},
			after: () => {
				processing.value = undefined
			},
		},
	})
}
</script>

<template layout>
	<div class="items-start gap-4 grid grid-cols-3">
		<card
			title="Characters"
			description="Toggle a character like to apply an optimistic property update before the server response arrives."
			class="col-span-2"
		>
			<div class="gap-3 grid">
				<div
					v-for="character in props.characters"
					:key="character.id"
					class="flex justify-between items-start gap-4 p-4 border border-default rounded-md"
				>
					<div class="min-w-0">
						<div class="flex items-center gap-2">
							<h2 class="font-medium text-highlighted" v-text="character.name" />
						</div>
						<p class="text-toned text-sm" v-text="character.title" />
						<p class="text-muted text-sm" v-text="character.description" />
					</div>
					<div class="flex items-center gap-3 shrink-0">
						<div class="text-right">
							<div class="font-medium tabular-nums" v-text="character.likes" />
							<div class="text-muted text-xs">likes</div>
						</div>
						<u-button
							:icon="character.liked ? 'lucide:heart' : 'lucide:heart-plus'"
							:color="character.liked ? 'primary' : 'neutral'"
							:variant="character.liked ? 'solid' : 'subtle'"
							:loading="processing === character.id"
							:aria-label="`${character.liked ? 'Unlike' : 'Like'} ${character.name}`"
							@click="toggleCharacterLike(character)"
						/>
					</div>
				</div>
			</div>
		</card>
		<card title="Debug" class="col-span-1">
			<div class="flex flex-col gap-4">
				<u-checkbox v-model="simulateServerError" label="Simulate validation error" />

				<div class="p-3 border border-default rounded-md text-sm">
					<div class="flex justify-between items-center gap-3">
						<span class="text-muted">Last request</span>
						<u-badge v-if="lastResult === 'success'" color="success" variant="subtle">
							Committed
						</u-badge>
						<u-badge v-else-if="lastResult === 'error'" color="error" variant="subtle">
							Rolled back
						</u-badge>
						<u-badge v-else color="neutral" variant="subtle">
							Waiting
						</u-badge>
					</div>
				</div>

				<div class="flex justify-end">
					<u-button
						label="Reload characters"
						icon="lucide:refresh-cw"
						color="neutral"
						variant="subtle"
						@click="router.reload({ only: ['characters'] })"
					/>
				</div>
			</div>
		</card>
	</div>
</template>
