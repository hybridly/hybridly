---
outline: [2, 3]
---

# Optimistic responses

<p class="preface">
Learn how to update view properties immediately while a hybrid request is still pending, then commit or roll back those changes when the server responds.
</p>

## Overview

Optimistic responses let you make the interface react before the network request finishes. This is useful for small, reversible interactions such as liking an item, toggling a flag, removing a row, or applying a local preview of a server-side action.

Use the [`updateImmediately`](../api/router/options.md#updateimmediately) router option to return a partial property update. Hybridly renders that update before the request is sent, then replaces it with the server response when the request succeeds. If the request fails or returns validation errors, the optimistic update is rolled back.

```ts
const liked = !character.liked

router.post(route('characters.like'), {
	data: {
		id: character.id,
		liked,
	},
	updateImmediately: (properties) => ({
		characters: properties.characters.map((current) =>
			current.id === character.id
				? {
					...current,
					liked,
					likes: current.likes + (liked ? 1 : -1),
				}
				: current
		),
	}),
})
```

## Updating properties

The callback receives the currently rendered view properties and must return only the top-level properties that should be replaced.

```ts
updateImmediately: ;
;((properties) => ({
	characters: properties.characters.map((character) => ({
		...character,
		selected: character.id === selectedId,
	})),
}))
```

The callback may run more than once while pending requests settle, so it should be pure and derive the next state only from the `properties` argument and stable values captured before the request starts.

```ts
const liked = !character.liked

router.post(route('characters.like'), {
	data: { id: character.id, liked },
	updateImmediately: (properties) => ({
		characters: properties.characters.map((current) =>
			current.id === character.id
				? { ...current, liked }
				: current
		),
	}),
})
```

## Server responses

The server should still return the final property values. Optimistic updates only affect the temporary client-side state while the request is pending.

```php
use function Hybridly\properties;

return properties([
	'characters' => $characters->setLiked(
		id: $request->integer('id'),
		liked: $request->boolean('liked'),
	),
]);
```

Validation errors roll back the optimistic state and trigger the usual validation lifecycle hooks.

```ts
router.post(route('characters.like'), {
	data: { id: character.id },
	updateImmediately: (properties) => ({
		characters: properties.characters.filter((current) =>
			current.id !== character.id
		),
	}),
	hooks: {
		'validation-error': () => {
			// Show an error state, toast, or retry action.
		},
	},
})
```

## Mergeable properties

Optimistic updates work with [mergeable properties](./partial-reloads.md#mergeable-properties). When a response merges a property touched by the optimistic update, Hybridly uses the optimistic value as the merge base so local removals or edits are not reintroduced by the merge operation.

For tables, update both `records` and `cells` so row data and rendered cell metadata stay synchronized.

```ts
import type { RecordIdentifier, Table } from 'hybridly/vue'

interface UserRecord {
	id: number
	name: string
	email: string
}

interface Properties {
	users: Table<UserRecord, 'simple'>
}

function removeRows(
	table: Table<UserRecord, 'simple'>,
	ids: RecordIdentifier[],
): Table<UserRecord, 'simple'> {
	const removedIds = new Set(ids)

	return {
		...table,
		records: table.records.filter((record) => !removedIds.has(record.id)),
		cells: table.cells.filter((cell) =>
			cell.key === null || !removedIds.has(cell.key)
		),
	}
}

router.post<Properties>(route('users.delete'), {
	data: { id: user.id },
	only: ['users'],
	updateImmediately: (properties) => ({
		users: removeRows(properties.users, [user.id]),
	}),
})
```
