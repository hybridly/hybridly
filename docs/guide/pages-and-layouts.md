# Pages and layouts

## Overview

With Hybridly, a page in your application consists of a regular <a href="https://vuejs.org/guide/scaling-up/sfc.html">single-file component</a>.

Serving a page consists of returning a [hybrid response](./responses.md) with the component name and optional properties.

## Pages

Pages are basic single-file components that receive data from controller as their properties. You can access them like any other single-file component using `defineProps`.

```vue
<!-- resources/views/pages/users/show.vue -->
<script setup lang="ts">
import { useHead } from '@vueuse/head'

const $props = defineProps<{
  user: App.Data.User
}>()

useHead({
  title: () => `${user.full_name}'s profile`
})
</script>

<template>
  <section>
    <h1 v-text="user.full_name" />
    <!-- ... -->
  </section>
</template>
```

The route and controller for the page above could look like the following:

```php
// routes/web.php
Route::get('/users/{user}', ShowUserController::class)->name('users.show');
```
```php
// app/Http/Controllers/Users/ShowUserController.php
use App\Data\UserData;
use App\Models\User;

class ShowUserController
{
    public function __invoke(User $user)
    {
        return hybridly('users.show', [
            'user' => UserData::from($user)
        ]);
    }
}
```

## Layouts

A page layout can be a basic single-file component as well. You can simply wrap your page's content in the layout component, like you would do in a basic Vue project.

```vue
<script setup lang="ts">
import MainLayout from '@/views/layouts/main.vue'
</script>

<template>
  <MainLayout>
    <!-- ... -->
  </MainLayout>
</template>
```

However, this technique has a drawback: when navigating between pages, the layout will be destroyed and re-mounted. This means you cannot have persistent state in it.

## Persistent layouts

Persistent layouts are components that will not get destroyed upon navigation. Their state will be persisted when changing pages.

A persistent layout can be defined in two ways. When using the [Layout plugin](./configuration/vite), a shortcut is available:

```vue
<template layout="main">
  <!-- ... -->
</template>
```

In the example above, the `resources/views/layouts/main.vue` layout will be used. If the name of the layout is omitted, the `layouts/default.vue` will be used instead. So if you only have one layout, you can use the following:

```vue
<template layout>
  <!-- ... -->
</template>
```

The other way to define a persistent layout is to use the `defineLayout` composable:

```vue
<script setup lang="ts">
import main from '@/views/layouts/main.vue'

defineLayout(main)
</script>

<template>
	<!-- ... -->
</template>
```

You can optionally pass [layout properties](#persistent-layout-properties) as the second argument of `defineLayout`.


:::info Named slots
Persistent layouts also have their own drawbacks. Specifically, it is not possible to use named slots with them. Instead, use properties or basic single-file component layouts.
:::

## Persistent layout properties

Additional properties can be passed to persistent layouts on a per-page basis. When navigating away from a page, the properties will be reset. 

You may use the `defineLayoutProperties` composable to define the layout properties:

```vue
<!-- resources/views/pages/example.vue -->
<script setup lang="ts">
defineLayoutProperties({
	fluid: true,
})
</script>

<template layout="main">
	<!-- ... -->
</template>
```

```vue
<!-- resources/views/layouts/main.vue -->
<script setup lang="ts">
defineProps<{
  fluid?: boolean
}>()
</script>

<template>
	<main :class="{
    'container mx-auto': fluid,
    'w-full mx-10': !fluid,
  }">
    <slot />
  </main>
</template>
```

Note that properties are not made reactive by default. Similar to Vue's `inject`/`provide`, you will need to make them reactive yourself.
