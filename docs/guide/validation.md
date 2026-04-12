---
outline: [2, 3]
---

# Validation

<p class="preface">
Learn how to work with validation errors globally, or scope them per form when multiple forms coexist on the same page.
</p>

## Overview

Hybridly handles validation errors natively. On the front-end, you can access them in three main ways:

- [`useForm`](../api/utils/use-form.md) exposes an `errors` record and some utilities
- [`<Form>`](../api/components/form.md) exposes the same utilities as `useForm`
- `useValidation` and `useValidationBag` provide global access to validation errors

## Validation with `useForm`

When using `useForm`, validation errors are automatically available in `form.errors` after a failed submission.

```vue
<script setup lang="ts">
const register = useForm({
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
		<input v-model="register.fields.name" type="text" />
		<!-- [!code hl] -->
		<p v-if="register.errors.name" v-text="register.errors.name" />

		<input v-model="register.fields.email" type="email" />
		<!-- [!code hl] -->
		<p v-if="register.errors.email" v-text="register.errors.email" />

		<input v-model="register.fields.password" type="password" />
		<!-- [!code hl] -->
		<p v-if="register.errors.password" v-text="register.errors.password" />

		<button type="submit" :disabled="register.processing">
			Submit
		</button>
	</form>
</template>
```

You may also control errors manually with `clearError`, `clearErrors` and `setErrors`. Read more in the [useForm documentation](../api/utils/use-form.md#validation-errors).

## Validation with `<Form>`

The [`<Form>`](../api/components/form.md) component exposes the same validation error utilities as `useForm` in its `#default` slot.

```vue
<template>
	<Form #default="{ errors, getError, processing, submit, clearErrors }">
		<!-- ... -->
	</Form>
</template>
```

In addition to `errors`, `<Form>` provides `getError(key)` to read nested paths and utility methods such as `clearErrors`, `reset`, and `resetFields`.

## Validation bags

For pages with multiple forms, error bags prevent collisions between unrelated validation errors that could share the same property names.

Set a bag on the form with the `errorBag` option:

```ts
const spellDiscoveryForm = useForm({
	url: route('spells.discover'),
	method: 'POST',
	errorBag: 'spell_discovery', // [!code hl]
	fields: {
		name: '',
		risk_level: '',
	},
})
```

On the `<Form>` component, this option is also available using the `error-bag` attribute.

## `useValidation` and `useValidationBag`

If you need access to validation errors globally, you may use `useValidation` or `useValidationBag`.

```ts
const errors = useValidationBag()
const spellDiscoveryErrors = useValidationBag('spell_discovery')
```

## Notes

- If no bag is specified, Hybridly uses the `default` bag.
- When `errorBag` is specified on a request, errors are scoped to that bag on the front-end.
- `useForm` and `<Form>` are generally the most convenient way to keep field values, pending state, and validation in sync.
