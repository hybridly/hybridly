# Validation

## Sharing errors

By default, validation errors are automatically shared to the `errors` [global property](./global-properties.md).

This behavior can be disabled by setting the `$shareValidationErrors` property to `false`. Alternatively, the `resolveValidationErrors` method of the middleware can be overriden to customize the behavior.

## Displaying errors

When using [forms](./forms.md), validation errors are stored in the `errors` property. It will be properly typed according to the fields defined in the form.

```vue
<script setup lang="ts">
const login = useForm({
	method: 'POST',
	url: route('login'),
	fields: {
		email: '',
		password: '',
	},
})
</script>

<template>
  <!-- ..... -->
  <span v-if="login.errors.email" v-text="login.errors.email" />
</template>
```

If you are validating data without forms, you can access errors through global properties — though they won't be automatically typed.

```vue
<script setup lang="ts">
const $props = defineProps<{
  errors: Record<string, string>
}>()

const properties = useProperties()
// properties.errors
</script>
```

## Errors bags

When validating multiple fields with the same name on the same page, conflicts may arise. 

For instance, a form that creates a company on the same page than a form that creates a user, both having a `name` field, will display the `errors.name` if the form that creates the user is submitted and the `name` field doesn't pass the validation.

To avoid that, you may use an error bag when making a request:

```ts
router.post(url, {
  errorBag: 'create-company',
  data,
})
```

This only happens when errors are accessed through global properties. The above does not apply when using [forms](./forms.md), because errors are automatically scoped to the form object.
