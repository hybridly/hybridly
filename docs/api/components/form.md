---
outline: [2, 3]
---

# `<Form>`

This component provides a native `<form>` API powered by Hybridly's [`useForm`](../utils/use-form.md) composable.

It reads fields from native controls (`name` attributes), submits using hybrid requests, and exposes the form state through its default slot.

## Usage

```vue
<template>
	<Form :action="route('profile.update')">
		<input name="name" />
		<button type="submit">Update profile</button>
	</Form>
</template>
```

## Attributes

### `action`

- **Type**: `string`

Defines the target URL. If omitted, it falls back to the underlying form `action` attribute, then to the current URL.

### `method`

- **Type**: `GET`, `POST`, `PUT`, `PATCH` or `DELETE`
- **Default**: `POST`

Defines the HTTP method used when submitting.

### `options`

- **Type**: `Omit<HybridRequestOptions, 'url' | 'data' | 'method' | 'errorBag' | 'progress'>`

Defines request options forwarded to form submission. This is the same as passing options to [`submit`](../utils/use-form.md#submit).

### `errorBag`

- **Type**: `string`

Defines the validation error bag used by this form.

### `show-progress`

- **Type**: `boolean`

Defines whether request upload/download progress should be tracked.

### `disable-while-processing`

- **Type**: `boolean`
- **Default**: `false`

When set to `true`, the native `inert` attribute is applied while the form is processing.

### `reset-on-success`

- **Type**: `boolean`
- **Default**: `true`

Defines whether native controls should reset to defaults after a successful submission.

### `set-default-on-success`

- **Type**: `boolean`
- **Default**: `false`

When set to `true`, current field values become the new default values after a successful submission.

## Default slot

The default slot receives most of the [`useForm`](../utils/use-form.md) return values, plus additional helpers:

- `errors`: nested validation errors object
- `getError(key: string)`: reads nested errors with dot notation
- `submit(options?)`: submits with optional overrides
- `reset()`: clears submission flags and errors, then resets controls
- `resetFields(...keys: string[])`: resets specific named fields or all fields

```vue
<template>
	<Form
		:action="route('spells.discover')"
		reset-on-success
		disable-while-processing
		#default="{ processing, submit, getError, reset }"
	>
		<input name="spell_name" />
		<p v-if="getError('spell_name')" v-text="getError('spell_name')" />

		<button type="button" @click="reset()">Reset</button>
		<button type="submit" :disabled="processing" @click.prevent="submit()">Submit</button>
	</Form>
</template>
```
