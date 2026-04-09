# Routing

<p class="preface">
Learn how to render hybrid views and how to generate URLs on the front-end.
</p>

## Overview

Hybridly, as opposed to single-page application, does not require redefining routes on the front-end. You only need to register routes and their controller actions as you would do in a normal Laravel application.

The only catch is to return a <abbr title="A view that respects the Hybridly protocol">hybrid view</abbr> instead of a Blade view. This is done by using the [`Hybridly\view`](../api/laravel/functions.md#view) function:

```php
use App\Data\UserData;
use App\Models\User;

use function Hybridly\view; // [!code hl]

final readonly class ShowUserController
{
    public function __invoke(User $user)
    {
        return view('users.show', [ // [!code hl]
            'user' => UserData::from($user)
        ]);
    }
}
```

Learn more about sending responses in [their documentation](./responses.md).

## Generating URLs

Since views are written in single-file components, global Laravel or PHP functions like `route` are not available.

Instead, you may use Hybridly's [`route`](../api/utils/route) util. This function is typed, which means you get TypeScript autocompletion and static analysis.

:::code-group

```php [routes/web.php]
Route::get('/', ShowIndexController::class)
  ->name('index')

Route::get('/users/{user}', [UsersController::class, 'show'])
  ->name('users.show')
```

```vue [resources/index.view.vue]
<script setup lang="ts">
import { route } from 'hybridly/vue'
</script>

<template>
	<a :href="route('users.show', { user: 1 })">
		Show the first user
	</a>
</template>
```

```ts [resources/util.ts]
import { route } from 'hybridly/vue'

route('index')
route('users.show', { user: 1 })
```

:::

:::info Reactivity
The `route` function is not reactive. It returns a `string`, not a `Ref<string>`.
:::

## Excluding or including routes

By default, vendor routes are not made available to the front-end and will not appear when using the `route` util.

This can be configured by updating the `router.allowed_vendors` key in `config/hybridly.php`.

```php
return [
      'router' => [
        'allowed_vendors' => [ // [!code focus:3]
            'laravel/fortify',
        ],
        'exclude' => [],
    ],
];
```

Additionally, you may exclude specific routes by adding patterns to the `router.exclude` key. These filters are matched against **the final URI**, and support wildcards.

```php
return [
      'router' => [
        'allowed_vendors' => [],
        'exclude' => [ // [!code focus:3]
            'admin*'
        ],
    ],
];
```
