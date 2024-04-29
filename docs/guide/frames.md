---
outline: 'deep'
---

# Frames

## Overview

Frames are a way to dynamically load a component and its properties from the server, similarly to page components.

They are defined using the `hybrid-frame` component, and their properties are loaded when mounted through an AJAX request using the `href` property.

:::info Experimental
This function has not been dogfed yet and is considered experimental. Its API may change at any time. Feel free to give feedback on our Discord server.
:::

## Creating a frame

You may create a frame by using the `hybrid-frame` component. Its `href` property must lead to an endpoint that returns the result of the `frame` function:

:::code-group
```vue-html [index.vue]
<hybrid-frame
  :href="route('users.show-profile-card', { user: $props.user.id })"
/>
```

```php [ShowProfileCardController.php]
use function Hybridly\frame; // [!code focus]

final class ShowProfileCardController
{
    public function __invoke(User $user)
    {
        return frame('users.profile-card', [ // [!code focus:3]
            'user' => UserProfileData::from($user),
        ]);
    }
}
```
:::

In the example above, the `users.profile-card` component lives among page components and works the same way.

## Defining the component on the client

It is not always useful to return the component name from the server. Instead, you may specify the `component` property on the `hybrid-frame` component:

```vue-html
<hybrid-frame
	component="users.profile-card"
  :href="route('users.show-profile-card', { user: $props.user.id })"
/>
```

This way, you may omit it in the server's response:

```php
return frame(properties: [
    'user' => UserProfileData::from($user),
]);
```

## Using a slot

Alternatively, you may use the default slot instead of using a component.

```vue-html
<hybrid-frame
	v-slot="{ user }: { user: App.Data.UserProfileData }"
	:href="route('users.show-profile-card')"
>
	<!-- Do something with `user` -->
  {{ user }}
</hybrid-frame>
```

## Updating a frame

You may unload a frame by clearing its `href` property, or update the frame by changing its value.

```vue
<script setup lang="ts">
const href = ref()

function unload() {
	href.value = undefined
}

function load(target: string) {
	href.value = target
}
</script>

<template>
	<button @click="load(route('frame.1'))">Load frame 1</button>
	<button @click="load(route('frame.2'))">Load frame 2</button>
	<button @click="unload">Clear</button>

	<hybrid-frame :href="href" />
</template>
```
