<script setup lang="ts">
import { type RecordIdentifier, router, type Table, useTable } from 'hybridly/vue'
import Card from '~/app/Components/card.vue'

interface UserRecord {
	id: number
	name: string
	email: string
	updated_at: string
}

interface Properties {
	users: Table<UserRecord, 'simple'>
}

const $props = defineProps<Properties>()

const users = useTable(() => $props.users)
const deleting = ref<RecordIdentifier>()

const nextPageUrl = computed(() => users.paginator.meta.next_page_url)

function removeRecordsFromTable(
	table: Table<UserRecord, 'simple'>,
	ids: RecordIdentifier[],
): Table<UserRecord, 'simple'> {
	const deletedIds = new Set(ids)

	return {
		...table,
		records: table.records.filter((record) => !deletedIds.has(record.id)),
		cells: table.cells.filter((cell) => cell.key === null || !deletedIds.has(cell.key)),
	}
}

function loadMore() {
	if (!nextPageUrl.value) {
		return
	}

	return router.get(nextPageUrl.value, {
		only: ['users'],
		preserveUrl: true,
		preserveScroll: true,
		preserveState: true,
		replace: true,
	})
}

function deleteUser(id: RecordIdentifier) {
	deleting.value = id

	return router.post<Properties>(route('kitchen-sink.tables.optimistic-merge.delete'), {
		data: { id },
		preserveScroll: true,
		preserveState: true,
		replace: true,
		updateImmediately: (properties) => ({
			users: removeRecordsFromTable(properties.users, [id]),
		}),
		hooks: {
			after: () => {
				deleting.value = undefined
			},
		},
	})
}

function resetDeletedRows() {
	return router.post(route('kitchen-sink.tables.optimistic-merge.reset'), {
		preserveScroll: true,
		preserveState: true,
		replace: true,
	})
}

useHead({
	title: 'Optimistic table row removal',
})
</script>

<template layout>
	<div class="flex flex-col gap-4 overflow-hidden grow">
		<card title="Optimistic table row removal">
			<template #description>
				<p>
					Load more users, then delete a row. The optimistic update removes matching records and cells locally while the
					redirect response returns a fresh page merged by record and cell keys.
				</p>
				<p class="mt-2">
					Note that restoring rows will not reorder them, as the merge is append-only. Similarly, a removal on page 1
					will reload the first page and thus add another row.
				</p>
			</template>

			<div class="flex flex-col gap-4 min-h-0">
				<div class="border border-default rounded-md overflow-auto">
					<table class="w-full text-sm">
						<thead class="bg-muted/40 text-muted">
							<tr>
								<th class="px-3 py-2 font-medium text-left">#</th>
								<th class="px-3 py-2 font-medium text-left">Name</th>
								<th class="px-3 py-2 font-medium text-left">Email</th>
								<th class="px-3 py-2 font-medium text-left">Updated</th>
								<th class="px-3 py-2 w-16 font-medium text-right">Actions</th>
							</tr>
						</thead>
						<tbody>
							<tr v-for="{ key, recordKey, value } in users.records" :key class="border-default border-t">
								<td class="px-3 py-2 tabular-nums">{{ recordKey }}</td>
								<td class="px-3 py-2">{{ value('name') }}</td>
								<td class="px-3 py-2 text-muted">{{ value('email') }}</td>
								<td class="px-3 py-2 text-muted">{{ value('updated_at') }}</td>
								<td class="px-3 py-2 text-right">
									<u-button
										icon="lucide:trash-2"
										color="error"
										variant="subtle"
										size="xs"
										:loading="deleting === recordKey"
										:aria-label="`Delete user ${recordKey}`"
										@click="deleteUser(recordKey!)"
									/>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>

			<template #footer>
				<div class="flex flex-wrap justify-end items-center gap-2">
					<u-button label="Load more" icon="lucide:plus" :disabled="!nextPageUrl" @click="loadMore" />
					<u-button
						label="Reset deleted rows"
						icon="lucide:rotate-ccw"
						color="neutral"
						variant="subtle"
						@click="resetDeletedRows"
					/>
				</div>
			</template>
		</card>
	</div>
</template>
