---
outline: deep
---

# Views and layouts

<p class="preface">
Learn how to render Vue pages and how to use persistent layout to preserve data and state during navigations.
</p>

## Overview

With Hybridly, a view in your application consists of a regular <a href="https://vuejs.org/guide/scaling-up/sfc.html">single-file component</a>.

Serving a view consists of returning a [hybrid response](./responses.md) with the component name and optional properties.

## Views

Views are basic single-file components that can receive data from controllers as their properties. You can access these properties like any other single-file component using `defineProps`.

:::code-group

```vue [resources/users/show.vue]
<script setup lang="ts">
const $props = defineProps<{
	user: App.Data.User
}>()

useHead({
	title: () => `${$props.user.full_name}'s profile`,
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

```php [ShowUserController.php]
use App\Data\UserData;
use App\Models\User;

use function Hybridly\view;

final readonly class ShowUserController
{
    public function __invoke(User $user)
    {
        return view('users.show', [
            'user' => UserData::from($user),
        ]);
    }
}
```

```php [web.php]
Route::get('/users/{user}', ShowUserController::class)
  ->name('users.show');
```

:::

:::info
Note that the `view` function is the one provided by Hybridly, not the one provided by Laravel. It is namespaced as `Hybridly\view`.
:::

## Layouts

The most basic layout can just be Vue component with a [slot](https://vuejs.org/guide/components/slots). You can wrap your view's content in the layout component, like you would do in any other Vue project.

:::code-group

```vue [resources/index.view.vue]
<script setup lang="ts">
import DefaultLayout from './default.layout.vue'
</script>

<template>
	<default-layout>
		<!-- ... -->
	</default-layout>
</template>
```

```vue [resources/default.layout.vue]
<template>
	<u-app>
		<u-header />
		<main>
			<slot /> <!-- [!code hl] -->
		</main>
	</u-app>
</template>
```

:::

However, this technique has a drawback: when navigating between views, the layout will be destroyed and re-mounted. This means you cannot have persistent state in it.

## Persistent layouts

Persistent layouts are components that will not get destroyed upon navigation. Their state will be persisted when changing views.

A persistent layout can be defined in two ways. You can use the `layout` attribute on the `template` element of the view component:

```vue
<template layout="default">
	<!-- ... -->
</template>
```

In the example above, the `resources/default.layout.vue` layout will be used.

:::warning Named slots
Persistent layouts also have their own drawbacks. Specifically, it is not possible to use named slots with them. Instead, use properties or basic single-file component layouts.
:::

## Default conventions

By default, Hybridly expects view and layout files to be stored somewhere in the `resources` directory.

It is not recommended to store them in `resources/views` or `resources/layouts`, because that would require you to include the subdirectory name in the component identifier. For instance, `resources/views/users/index.view.vue` would be identified as `views.users.index` instead of `users.index`.

Moreover, you are required to suffix them with `.view.vue` or `.layout.vue`. This is the conventions we chose for finding Vue files because of their explicitness and similarity with Blade file conventions, which are suffixed by `.blade.php`.

### How it works

When the development server starts (`vite dev`) or when the production build is generated (`vite build`), the Laravel adapter of Hybridly scans the codebase for view and layout files, then shares them as a identifier/path map to the Vite plugin.

When receiving a component identifier like `users.show` from a controller, Hybridly is able to render the right component thanks to that information.

### Storing components elsewhere

Hybridly defaults to `resources` because of the typical Laravel developer's familiarity with that directory. However, it is possible to configure it to find views and layouts anywhere in your codebase.

You may refer to the [architecture](./architecture.md) documentation to learn how to do that.
