<script setup lang="ts">
import { router, useTable, type Table } from 'hybridly/vue'
import Card from '~/app/Components/card.vue'

interface UserRecord {
	id: number
	name: string
	email: string
	updated_at: string
}

const props = defineProps<{
	users: Table<UserRecord>
}>()

const users = useTable(() => props.users)

const recordIds = computed(() => users.data.map((record) => record.id).join(', '))
const latestGeneration = computed(() => users.records.at(0)?.extra('name', 'generation') ?? 0)

async function reloadRepeatedly() {
	for (let index = 0; index < 3; index++) {
		await router.reload({ only: ['users'] })
	}
}

useHead({
	title: 'Mergeable table',
})
</script>

<template layout>
	<div class="flex flex-col gap-4 overflow-hidden grow">
		<card title="Mergeable table">
			<template #description>
				<p>
					Each partial reload returns the same five record IDs with fresh values. The row count should stay stable while
					the batch number changes.
				</p>
			</template>

			<div class="flex flex-col gap-4 min-h-0">
				<div class="grid grid-cols-1 md:grid-cols-3 gap-3">
					<div class="rounded-md border border-default px-3 py-2">
						<div class="text-xs text-muted">Rows</div>
						<div class="font-medium">{{ users.records.length }}</div>
					</div>
					<div class="rounded-md border border-default px-3 py-2">
						<div class="text-xs text-muted">Latest batch</div>
						<div class="font-medium">{{ latestGeneration }}</div>
					</div>
					<div class="rounded-md border border-default px-3 py-2">
						<div class="text-xs text-muted">Record IDs</div>
						<div class="font-medium truncate">{{ recordIds }}</div>
					</div>
				</div>

				<div class="overflow-auto rounded-md border border-default">
					<table class="w-full text-sm">
						<thead class="bg-muted/40 text-muted">
							<tr>
								<th class="w-10 px-3 py-2 text-left font-medium">
									<u-checkbox :model-value="users.isPageSelected" @update:model-value="users.toggleAll(Boolean($event))" />
								</th>
								<th v-for="column in users.columns" :key="column.name" class="px-3 py-2 text-left font-medium">
									{{ column.label }}
								</th>
								<th class="px-3 py-2 text-left font-medium">Cell extra</th>
							</tr>
						</thead>
						<tbody>
							<tr v-for="{ key, recordKey, hasKey, selected, toggle, value, extra } in users.records" :key class="border-t border-default">
								<td class="px-3 py-2">
									<u-checkbox
										:model-value="selected"
										:disabled="!hasKey"
										@update:model-value="toggle(Boolean($event))"
									/>
								</td>
								<td class="px-3 py-2 tabular-nums">{{ recordKey }}</td>
								<td class="px-3 py-2">{{ value('name') }}</td>
								<td class="px-3 py-2 text-muted">{{ value('email') }}</td>
								<td class="px-3 py-2 text-muted">{{ value('updated_at') }}</td>
								<td class="px-3 py-2">
									<span class="rounded bg-muted px-2 py-1 text-xs">
										{{ extra('name', 'priority') }} / batch {{ extra('name', 'generation') }}
									</span>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>

			<template #footer>
				<div class="flex flex-wrap justify-between items-center gap-3">
					<div class="flex gap-4 text-muted text-sm">
						<span>Selected: {{ users.selection.only.length }}</span>
						<span>Data rows: {{ users.data.length }}</span>
					</div>
					<div class="flex flex-wrap items-center gap-2">
						<u-button label="Partial reload" icon="lucide:refresh-cw" @click="router.reload({ only: ['users'] })" />
						<u-button label="Reload 3 times" icon="lucide:repeat" @click="reloadRepeatedly" />
						<u-button label="Reset" icon="lucide:rotate-ccw" @click="router.reload({ reset: ['users'], only: ['users'] })" />
					</div>
				</div>
			</template>
		</card>
	</div>
</template>
