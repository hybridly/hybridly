# Forms

## Overview

The best practice regarding forms is to intercept a form submission and make the request using the form util.

It is also possible to make a classic form submission, but it is not recommended, as it will cause a full page reload.

:::info AJAX is still an option
Some situations might require more control over the form submission — in such cases, it's perfectly fine to make plain AJAX requests instead of hybrid ones.
:::

## The form util

Since most forms share the same kind of boilerplate code, Hybridly comes with a form util that helps with keeping the code minimal.

```vue
<script setup lang="ts">
const login = useForm({ // [!code focus:8]
	method: 'POST',
	url: route('login'),
	fields: {
		email: '',
		password: '',
	},
})
</script>

<template>
	<form @submit.prevent="login.submit">  // [!code focus:14]
		<!-- Email -->
		<input v-model="login.fields.email" type="email"/>
		<span v-if="login.errors.email" v-text="login.errors.email" />

		<!-- Password -->
		<input v-model="login.fields.password" type="password"/>
		<span v-if="login.errors.password" v-text="login.errors.password" />

		<!-- Submit -->
		<button type="submit" :disabled="login.processing">
			Sign in
		</button>
	</form>
</template>
```

:::info Hybrid responses
Note that no arbitrary data can be returned from a hybrid request. Instead, you should redirect back to a page — which can totally be the same page the request comes from.
:::

## Form options

The form util takes a single object as its arguments. This object must contain at least a `fields` property. However, it's common to define the `method` and the `url` as well.

```ts
useForm({
	url: route('chirps.update'),
	method: 'PATCH',
	fields: {
		body: '',
	}
})
```

Other [visit options](../api/router/utils.md) are also available. Alternatively, you can define the `url` and other options when submitting the form.

```ts
const edit = useForm({
	fields: {
		body: '',
	}
})

edit.submitWithOptions({
	url: route('chirps.update'),
	method: 'PATCH',
})
```

Read about the [form composable](../api/composables/use-form.md) for more information.

## Request lifecycle

Sometimes, some custom logic needs to be executed during a request's  lifecycle. The form util has a `hook` property that can be used to catch these events.

```ts
useForm({
	fields: {
		body: '',
	},
	hooks: {
		start: () => console.log('The request has started.'),
		fail: () => console.log('The request has failed.'),
		finish: () => console.log('The request has finished.'),
	}
})
```

Read about the available hooks in their [documentation](./hooks.md).

## Transforming data

It's sometimes useful to transform data right before it's sent to the server, because the format used to interface with the user does not match the one the server expects.

When the `transform` property is given, the request will use its result:

```ts
useForm({
	fields: {
		email: '',
		password: '',
		remember: true,
	},
	transform: (fields) => ({
		...fields,
		remember: fields.remember ? 'on' : ''
	})
})
```

## Transforming URLs

A convenient way of transforming URLs is provided through the [visit options](../api/router/utils.md). 

For instance, clearing the query parameters is as simple as passing an empty string to the `search` property:

```ts
useForm({
	url: '/filter?sort=asc',  // [!code focus]
	fields: {
		// ...
	},
	transformUrl: { // [!code focus:3]
		search: ''
	}
})
```
