---
outline: [2, 3]
---

# `<WhenVisible>`

This headless component triggers a [partial reload](../../guide/partial-reloads.md) when it enters the viewport.

Internally, this component uses [`IntersectionObserver`](https://developer.mozilla.org/en-US/docs/Web/API/IntersectionObserver) and makes a [`router.reload`](../router/navigation.md#reload) request with the configured [`data`](#data) as `only` keys.

## Usage

```vue
<template>
	<when-visible data="stats">
		<div>Stats loaded.</div>
	</when-visible>
</template>
```

## Attributes

### `data`

- **Required**: true
- **Type**: `string | string[]`

Defines the property key, or keys, to reload when the component becomes visible.

### `as`

- **Type**: `string`
- **Default**: `'div'`

Defines which HTML tag to render as the wrapper element.

### `buffer`

- **Type**: `number`
- **Default**: `0`

Expands the observer margins by the given pixel value, so loading can start before the element is strictly visible.

### `root`

- **Type**: `string | Element | null`
- **Default**: `null`

Defines the scroll root used by `IntersectionObserver`. You may pass a CSS selector string or a DOM element.

### `once`

- **Type**: `boolean`
- **Default**: `true`

When `true`, the reload is only triggered once. When `false`, the component may reload again each time it re-enters the viewport.

### `fallbackOnHidden`

- **Type**: `boolean`
- **Default**: `false`

When `false`, the default slot keeps rendering after data has loaded, even when the element leaves the viewport. When `true`, the `fallback` slot is shown again while hidden.

### `options`

- **Type**: `Omit<HybridRequestOptions, 'url' | 'data' | 'method'>`

Additional reload options forwarded to [`router.reload`](../router/navigation.md#reload).

#### Usage

```vue
<template>
	<when-visible data="receivedAt" :options="{ preserveScroll: true, replace: true } /* [!code focus] */">
		<template #default>
			<div>Loaded.</div>
		</template>
	</when-visible>
</template>
```

## Slots

### `default`

The default slot exposes the following properties:

- `loading: boolean`: `true` when at least one tracked property is still missing.
- `reloading: boolean`: `true` when tracked properties are being reloaded.
- `loaded: boolean`: `true` when all tracked properties are loaded.

Rendered when the data is loaded.

### `fallback`

The `fallback` slots exposes the following properties:

- `loading: boolean`: `true` when at least one tracked property is still missing.
- `reloading: boolean`: `true` when tracked properties are being reloaded.
- `loaded: boolean`: `true` when all tracked properties are loaded.

Rendered while data is not loaded.

### `loading`

The `loading` slots exposes the following properties:

- `loading: boolean`: `true` when at least one tracked property is still missing.
- `reloading: boolean`: `true` when tracked properties are being reloaded.
- `loaded: boolean`: `true` when all tracked properties are loaded.

Emitted right before the visibility-triggered reload starts.
