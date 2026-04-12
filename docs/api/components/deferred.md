---
outline: [2, 3]
---

# `<Deferred>`

This headless component helps rendering [deferred properties](../../guide/partial-reloads.md#deferred-properties).

It exposes loading state through slots so you can display placeholders until deferred data is available.

## Usage

```vue
<template>
	<Deferred :data="['stats', 'chart'] /* [!code focus] */">
		<template #default="{ loaded }">
			<div v-if="loaded">Content loaded.</div>
		</template>

		<template #fallback>
			<div>Loading data...</div>
		</template>
	</Deferred>
</template>
```

## Attributes

### `data`

- **Required**: true
- **Type**: `string | string[]`

Defines the property key, or keys, to track.

## Slots

### `default`

The default slot exposes the following properties:

- `loading: boolean`: `true` when at least one tracked property is still missing.
- `reloading: boolean`: `true` when tracked properties are being reloaded.
- `loaded: boolean`: `true` when all tracked properties are loaded.

Rendered when all tracked properties are loaded.

### `fallback`

The `fallback` slots exposes the following properties:

- `loading: boolean`: `true` when at least one tracked property is still missing.
- `reloading: boolean`: `true` when tracked properties are being reloaded.
- `loaded: boolean`: `true` when all tracked properties are loaded.

Rendered while at least one tracked property is still missing.
