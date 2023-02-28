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

```php
// routes/web.php
Route::get('/', ShowIndexController::class)
  ->name('index')

Route::get('/users/{user}', [UsersController::class, 'show'])
  ->name('users.show')
```

```ts vue
// In a .vue or .ts file
route('index')
route('users.show', { user: 1 })
```

:::info Gotchas
The `route` function is not reactive, in the sense that it returns a `string`, not a `Ref`.
:::

## Configuration

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

Additionally, you may exclude specific routes by adding patterns to the `router.exclude` key.

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
