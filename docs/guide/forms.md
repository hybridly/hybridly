---
outline: [2, 3]
---

# Forms

<p class="preface">
Learn how to build and submit forms in a convenient way.
</p>

## Overview

Hybridly provides a [`<Form>`](../api/components/form.md) component for declaring forms in templates, and a [`useForm`](../api/utils/use-form.md) composable for fully controlled form state.

`<Form>` is the simplest option and should be enough for a lot of use cases. It works directly with native fields using `name` attributes, and offers utils in its default slot for working with validation errors.

Use `useForm` when you need full control over fields, custom interactions, or `v-model`-based inputs.

## Using `<Form>`

The `<Form>` component reads values from native controls and submits hybrid requests for you. The `action` attribute takes the URL to submit to, and the optional `method` attribute specifies the HTTP method.

```vue
<template>
	<Form :action="route('profile.update')" method="patch">
		<input name="name" />
		<input name="email" type="email" />
		<button type="submit">Save</button>
	</Form>
</template>
```

### Error bags

When multiple forms coexist, use the `error-bag` attribute to scope validation errors.

<!-- dprint-ignore -->
```vue
<template>
	<Form
    :action="route('profile.update')"
    error-bag="profile"
    #default="{ getError }"
  >
		<input name="name" />
		<span v-if="getError('name')" v-text="getError('name')" />
		<input name="email" type="email" />
		<span v-if="getError('email')" v-text="getError('email')" />
		<button type="submit">Save</button>
	</Form>

	<Form :action="route('password.update')" error-bag="password">
		<input name="current_password" type="password" />
		<span v-if="getError('current_password')" v-text="getError('current_password')" />
		<input name="new_password" type="password" />
		<button type="submit">Change password</button>
	</Form>
</template>
```

### Disable while processing

Use the `disable-while-processing` attribute to apply the native `inert` attribute while a request is being processed. This prevents any interaction with the form until the request is finished.

```vue
<template>
	<Form :action="route('profile.update')" disable-while-processing>
		<input name="name" />
		<input name="email" type="email" />
		<button type="submit">Save</button>
	</Form>
</template>
```

### Resetting the form

Use the `reset-on-success` attribute to reset fields after a successful submission. This can be useful for forms that create new resources, such as a comment form.

```vue
<template>
	<Form :action="route('comments.store')" reset-on-success>
		<input name="username" />
		<input name="comment" />
		<button type="submit">Post comment</button>
	</Form>
</template>
```

Optionally, you can specify specific fields to reset by passing an array of field names instead:

```vue
<template>
	<Form :action="route('comments.store')" :reset-on-success="['comment']">
		<input name="username" />
		<input name="comment" />
		<button type="submit">Post comment</button>
	</Form>
</template>
```

Similarly, you may use the `reset-on-error` attribute to reset fields after validation errors.

### Setting new default values

Use the `set-default-on-success` attribute to set new default values for fields after a successful submission.

```vue
<template>
	<Form :action="route('profile.update')" set-default-on-success>
		<input name="name" />
		<input name="email" type="email" />
		<button type="submit">Save</button>
	</Form>
</template>
```

### Default slot

`<Form>` exposes the underlying `useForm` state in its default slot, plus helpers such as `getError`, `reset`, and `resetFields`.

<!-- dprint-ignore -->
```vue
<template>
	<Form
    #default="{ processing, getError, submit, reset }"
    :action="route('spells.discover')"
  >
		<input name="name" />
		<p v-if="getError('name')" v-text="getError('name')" />

		<button type="button" @click="reset()">Reset</button>
		<button type="submit" :disabled="processing" @click.prevent="submit()">Submit</button>
	</Form>
</template>
```

## Using `useForm`

When more control is needed over form state, you may use the `useForm` composable instead.

Typically, the native form submission would be prevented using `@submit.prevent`, and the `submit()` helper would be called to trigger the request instead.

```vue
<script setup lang="ts">
const register = useForm<App.Users.RegisterRequest>({
	url: route('register'),
	method: 'POST',
	fields: {
		name: '',
		email: '',
		password: '',
	},
})
</script>

<template>
	<form @submit.prevent="register.submit()">
		<u-input v-model="register.fields.name" type="text" :error="register.errors.name" />
		<u-input v-model="register.fields.email" type="email" :error="register.errors.email" />
		<u-input v-model="register.fields.password" type="password" :error="register.errors.password" />
		<u-button type="submit" :loading="register.processing" label="Create account" />
	</form>
</template>
```

### Transforming data before submission

You may pass a `transform` function to `useForm` to adapt outgoing data before it is sent.

This can be useful for adjusting field values, such as converting a boolean to a string for checkboxes, or date objects to a format suitable for the backend.

```ts
const form = useForm({
	url: route('login'),
	method: 'POST',
	fields: {
		email: '',
		password: '',
		remember: true,
	},
	transform: (fields) => ({
		...fields,
		remember: fields.remember ? 'on' : '',
	}),
})
```

### Transforming URLs

A convenient way of transforming URLs is provided through the [request options](../api/router/options.md#transformurl).

For instance, clearing the query parameters is as simple as passing an empty string to the search property:

```ts
const form = useForm({
	url: '/filter?sort=asc',
	// ...
	transformUrl: {
		search: '',
	},
})
```

### Request lifecycle

Sometimes, you may need to execute custom logic during the request's lifecycle, such as showing a notification when a request starts or finishes.

The `useForm` util accepts [request options](../api/router/options.md#transformurl), which have a `hooks` property that can be used to catch these events.

```ts
const form = useForm({
	// ...
	hooks: {
		start: () => console.log('Request started'),
		'validation-error': () => console.log('Validation failed'),
		success: () => console.log('Request succeeded'),
		after: () => console.log('Request finished'),
	},
})
```
