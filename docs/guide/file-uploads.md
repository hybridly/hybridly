# File uploads

## Overview

When submitting data that include files, Hybridly will automatically convert the request data into a [`FormData` object](https://developer.mozilla.org/en-US/docs/Web/API/FormData/Using_FormData_Objects). This is necessary, as file uploads are only possible when a form encoding is set to `multipart/form-data`.

It's also possible to send a `FormData` object directly, or force the conversion using `useFormData`.

```ts
const data = new FormData()
// ...

router.post(url, {
	data
})
```

### Progress

When using the form util, a `progress` object is exposed. It contains the current progress `percentage` and the associated `AxiosProgressEvent`.

```ts
const form = useForm(/* ... */)
// form.progress?.percentage
// form.progress?.event
```

The example below shows how a file can be uploaded and its progress be displayed.

```vue
<script setup lang="ts">
const form = useForm<App.Data.UpdateProfileData>({
	method: 'POST',
	url: '/upload',
	fields: {
		first_name: '',
		profile_picture: undefined
	},
})

// A `file` input is not `v-model` able, so its `change`
// event needs to be used to find the uploaded file.
function onFileChange(event: Event) {
	const target = event.target as HTMLInputElement
	form.fields.profile_picture = target.files?.[0]
}
</script>

<template>
	<form @submit.prevent="form.submit">
		{{ form.progress?.percentage ?? 0 }}%

		<input v-model="form.fields.first_name" type="text" />
		<input @change="onFileChange" type="file" />
		<button>Submit</button>
	</form>
</template>
```

## Limitations

It is not natively supported by PHP to upload files using `multipart/form-data` and the `PUT`, `PATCH` or `DELETE` methods. 

Fortunately, Laravel supports [method spoofing](https://laravel.com/docs/9.x/routing#form-method-spoofing), which means you can add a `_method` field with `PUT`, `PATCH` or `DELETE` and send the request using `POST`.

```ts
const avatar = new File()
// ...

router.post(url, {
	data: {
		_method: 'PUT',
		avatar
	}
})
```
