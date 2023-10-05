# Views and layouts

## Overview

With Hybridly, a view in your application consists of a regular <a href="https://vuejs.org/guide/scaling-up/sfc.html">single-file component</a>.

Serving a view consists of returning a [hybrid response](./responses.md) with the component name and optional properties.

## Views

Views are basic single-file components that can receive data from controllers as their properties. You can access these properties like any other single-file component using `defineProps`.

:::code-group
```vue [resources/views/users/show.vue]
<script setup lang="ts">
const $props = defineProps<{
  user: App.Data.User
}>()

useHead({
  title: () => `${$props.user.full_name}'s profile`
})
</script>

<template>
  <section>
    <h1 v-text="user.full_name" />
    <!-- ... -->
  </section>
</template>
```
:::

The route and controller for the view above could look like the following:

:::code-group
```php [web.php]
Route::get('/users/{user}', ShowUserController::class)->name('users.show');
```
```php [ShowUserController.php]
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
:::

## Layouts

A view layout can be a basic single-file component as well. You can simply wrap your view's content in the layout component, like you would do in a basic Vue project.

```vue
<script setup lang="ts">
import MainLayout from '@/views/layouts/main.vue'
</script>

<template>
  <main-layout>
    <!-- ... -->
  </main-layout>
</template>
```

However, this technique has a drawback: when navigating between views, the layout will be destroyed and re-mounted. This means you cannot have persistent state in it.

## Persistent layouts

Persistent layouts are components that will not get destroyed upon navigation. Their state will be persisted when changing views.

A persistent layout can be defined in two ways. You can use the `layout` attribute on the `template` element of the view component:

```vue
<template layout="main">
  <!-- ... -->
</template>
```

In the example above, the `resources/views/layouts/main.vue` layout will be used. If the name of the layout is omitted, `layouts/default.vue` will be used instead. So if you only have one layout, you can use the following:

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


:::warning Named slots
Persistent layouts also have their own drawbacks. Specifically, it is not possible to use named slots with them. Instead, use properties or basic single-file component layouts.
:::

## Persistent layout properties

Additional properties can be passed to persistent layouts on a per-view basis. When navigating away from a view, the properties will be reset. 

You may use the `defineLayoutProperties` util to define the layout properties:

```vue
<!-- resources/views/views/example.vue -->
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
