---
outline: deep
---

# Functions

Hybridly exposes global and namespaced functions, for convenience.

## Global functions

These functions are available globally when `hybridly/laravel` is installed.

### `hybridly`

When called without a parameter, this functions returns the [`Hybridly\Hybridly`](./hybridly.md) singleton instance.

Otherwise, it is an alias of [`hybridly()->view()`](./hybridly.md#view).

## Namespaced functions

The functions live in the `\Hybridly` namespace and need to be imported before being used.

### `view`

Renders a view with the given component and optional properties. The properties can be an array, an `Arrayable` or a [data object](http://localhost:5173/guide/typescript.html#data-objects).

> See also: [responses](../../guide/responses.md)

```php
use function Hybridly\view;

return view('user.show', $user);
```

### `properties`

Returns updated properties for an existing view.

> See also: [properties-only responses](../../guide/responses.md#updating-properties)

```php
use function Hybridly\properties;

return properties([
	'user' => $user,
]);
```

### `partial`

Creates a [partial-only](../../guide/partial-reloads.md#partial-only-properties) property.

> See also: [partial properties](../../guide/partial-reloads.md#partial-only-properties)

```php
use function Hybridly\view;
use function Hybridly\partial;

return view('user.show', [
    'user' => $user,
    'posts' => partial(fn () => Post::forUser($user)->paginate()),
]);
```

### `deferred`

Creates a partial property that will automatically be loaded in a subsequent partial reload when the page loads.

> See also: [deferred properties](../../guide/partial-reloads.md#deferred-properties)

```php
use function Hybridly\view;
use function Hybridly\deferred;

return view('user.show', [
    'user' => $user,
    'posts' => deferred(fn () => Post::forUser($user)->paginate()),
]);
```

### `to_external_url`

Redirects to a non-hybrid page or an external domain.

> See also: [external redirects](../../guide/responses.md#external-redirects).

```php
use function Hybridly\to_external_url;

return to_external_url('https://google.com');
```

### `is_hybrid`

Determines whether the current request is hybrid. Optionally, a `Illuminate\Http\Request` instance can be given instead of using the current request.

```php
use function Hybridly\is_hybrid;

if (is_hybrid()) {
  // ...
}
```

### `is_partial`

Determines whether the current request is a [partial reload](../../guide/partial-reloads.md). Optionally, a `Illuminate\Http\Request` instance can be given instead of using the current request.

```php
use function Hybridly\is_partial;

if (is_partial()) {
  // ...
}
```

## Namespaced testing functions

### `partial_headers`

Generates headers for testing partial requests. The first parameter is the page component name, and the second and third parameters are an array of `only` and `except` properties, respectively.

> See also: [partial reloads](../../guide/partial-reloads.md)

```php
use function Hybridly\Testing\partial_headers;

get('/', partial_headers('users.show', only: ['posts']))
    ->assertMissingHybridProperty('user')
    ->assertHybridProperty('posts');
```
