---
outline: 'deep'
---

# `useForm`

This composable helps with defining and submiting forms.

## Usage

```ts
function useForm<T>(options: FormOptions<T>): FormReturn

interface FormOptions<T> extends HybridRequestOptions {
	fields: T
	key?: string | false
	timeout?: number
	reset?: boolean
	transform?: (fields: T) => T
}
```

`useForm` accepts a object of options, most of which are the same as [router's options](../router/options.md).

## Example

The following example is a typical `useForm` usage:

```ts
const login = useForm({
	method: 'POST',
	url: route('login'),
	fields: {
		email: '',
		password: '',
	},
})
```

## Form options

`useForm` uses most of the options available in the [router's options](../router/options.md). Additionally, the few options specific to this composable are documented below.

### `fields`

- **Type**: `object`
- **Required**: true

Defines the shape of the form data. It is mandatory and provides typings for other form functionality, such as `errors` or `transform`.

### `reset`

- **Type**: `boolean`
- **Default**: `true`

Defines whether the fields should be reset when the submission is successful.

### `updateInitials`

- **Type**: `boolean`
- **Default**: `false`

Defines whether the current fields should be set as the default fields after the submission is successful. If `true`, subsequent `reset` calls will reset the form to use these fields.

### `timeout`

- **Type**: `number`
- **Default**: `5000`

Defines the delay after which the `recentlySuccessful` and `recentlyFailed` variables are reset to `false`.

### `transform`

- **Type**: `(fields: T) => any`

Defines a transformer function that will be called before submitting data. The data submitted will be the result of this function instead of the fields.

### `key`

- **Type**: `string`
- **Default**: `false`

Defines the key under which the form should be remembered in the history state. This is disabled by default to avoid mixing form states when there are multiple ones in the same page.

## Form return values

`useForm` returns an object containing a few variables and utils.

### `fields`

- **Type**: `T`

A reactive variable containing the fields of the form. You can, for instance, use this variable with `v-model`.

```vue
<template>
  <input type="text" v-model="form.fields.name" />
</template>
```

### `submit`

- **Type**: `() => Promise<NavigationResponse>`

A function that submits the form. This function does not accept options because its purpose is to be used directly in a template when the form has been configured during its initialization.

```vue
<template>
  <form @submit.prevent="form.submit">
    <!-- ... -->
  </form>
</template>
```

### `submitWith`

- **Type**: `(options?: HybridRequestOptions) => Promise<NavigationResponse>`

A function that submits the form with the given options. These will override the options defined in the form's initialization.

```ts
form.submitWith({ url: '/login' })
```

### `hasErrors`

- **Type**: `boolean`

Returns whether the form has errors.

### `errors`

- **Type**: `Record<keyof T, string>`

An object containing the error for each one of the fields.

### `isDirty`

- **Type**: `boolean`

Returns whether the form has pending modifications relative to its [loaded](#loaded) state.

### `hasDirty`

- **Type**: `(...keys: keyof T) => boolean`

A function that returns a boolean value that indicates if the given fields are dirty.

### `processing`

- **Type**: `boolean`

Returns whether the form is being processed — that is, if a navigation triggered by this form is being made.

### `successful`

- **Type**: `boolean`

Returns whether the form's submission was successful. This value is reset when submitting the form again.

### `failed`

- **Type**: `boolean`

Returns whether the form's submission has failed. This value is reset when submitting the form again.

### `recentlySuccessful`

- **Type**: `boolean`

Returns whether the form's was recently successful. This value is reset after the [timeout](#timeout) defined in the form's option, or 5 seconds by default.

### `recentlyFailed`

- **Type**: `boolean`

Returns whether the form's has recently failed. This value is reset after the [timeout](#timeout) defined in the form's option, or 5 seconds by default.

### `reset`

- **Type**: `(...keys: keyof T) => void`

A function that resets the given fields, or all fields if none are given. All cleared fields will also have their errors cleared.

### `clear`

- **Type**: `(...keys: keyof T) => void`

A function that clears the given fields, or all fields if none are given. This is different than `reset`, in the sense that the fields will specifically be set to `undefined`.

### `progress`

- **Type**: `{ event: AxiosProgressEvent; percentage: number }`

A reactive object containing the current progress percentage. This object may be undefined when there is no pending navigation.

### `clearErrors`

- **Type**: `(...keys: keyof T) => void`

A function that clears the errors of the given fields, or all fields if none are given. This is automatically called by [`reset`](#reset).

### `clearError`

- **Type**: `(error: keyof T) => void`

A function that clear the error of the given field.

### `setErrors`

- **Type**: `(errors: Record<keyof T, string) => void`

A function that sets the given errors to the given fields. This is usually not needed and should only be used to handle edge-cases.

### `initial`

- **Type**: `T`

Returns a read-only object containing the initial fields defined when initializing the form.

### `loaded`

- **Type**: `T`

Returns a read-only object containing the fields loaded when instanciating the form. This object may be different than `initial` in case the form was remembered through the history state.

### `abort`

- **Type**: `() => void`

A function that aborts the submission. This is the same as calling [`router.abort()`](../router/utils.md#abort).
