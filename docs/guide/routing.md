# Routing

## Overview

As opposed to single-page applications, routes do not need to be defined on the front-end. Vue Router is not needed — simply define your routes in `routes/*.php` like you would do with normal Laravel applications.

The only catch is to return a <abbr title="A view that respects the Hybridly protocol">hybrid view</abbr> instead of a Blade view. This is done by using the `hybridly` function:

```php
use App\Data\UserData;
use App\Models\User;

class ShowUserController
{
    public function __invoke(User $user)
    {
        return view('users.show', [ // [!code --]
        return hybridly('users.show', [ // [!code ++]
            'user' => UserData::from($user)
        ]);
    }
}
```

Learn more about sending responses in the [their documentation](./responses.md).

## Generating URLs

Since pages are written in single-file components, global Laravel functions like `route` are not available.

Instead, you may use Hybridly's [`route`](../api/utils/route) util. This function is typed and its typings are updated live as your `routes/*.php` files are saved.

For it to work, a virtual import is needed:

```ts
import { createApp } from 'vue'
import { initializeHybridly } from 'hybridly/vue'
import 'virtual:hybridly/router' // [!code focus]

initializeHybridly({
	cleanup: !import.meta.env.DEV,
	pages: import.meta.glob('../views/pages/**/*.vue', { eager: true }),
	setup: ({ render, element, hybridly }) => createApp({ render })
		.use(hybridly)
		.mount(element),
})
```

This import is required by the Vite plugin to register the routes at the moment the development server is started and to set up hot-module replacement for them. 

```php
// routes/web.php
Route::get('/', ShowIndexController::class)
  ->name('index')

Route::get('/users/{user}', [UsersController::class, 'show'])
  ->name('users.show')
```

```ts vue
// In a .vue or .ts file
import { route } from 'hybridly/vue'

route('index')
route('users.show', { user: 1 })
```

:::info Gotchas
If the `route` function is used without its virtual import, or an unknown route name is given, an error will be thrown.

Additionally, the function is not reactive, in the sense that it returns a `string`, not a `Ref`.
:::
