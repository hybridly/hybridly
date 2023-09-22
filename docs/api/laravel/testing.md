---
outline: deep
---

# Testing

Hybridly provides a few resonse testing macros to reduce the boilerplate needed for the most common assertions.

## Response macros

### `assertHybridProperty`

This method asserts that the given hybrid property exists in the response and is equal to the given value. It accepts the same arguments as `AssertableJson#where`.

### `assertHybridProperties`

This method uses an array to perform assertions. Depending on the syntax, it may assert a property presence, its value, its count, or use a callback.

```php
$response->assertHybridProperties([
    'first_name', // asserts `first_name` exists
    'first_name' => fn ($name) => expect($name)->toBe('Jon'), // uses a callback to get the value and perform assertions on it
    'first_name' => 'Jon', // asserts `foo` exists and has the value `bar`
    'roles' => ['administrator', 'editor'], // asserts `roles` exists and contains the given properties
    'roles' => fn (Assertable $roles) => $roles->hasAll(['administrator', 'editor']), // same thing, using a callback and a typehinted parameter
    'roles' => 2, // asserts `roles` has 2 values
    'foo.bar' => 'value', // works for nested properties using dot notation as well
]);
```

### `assertHasHybridProperty`

This method asserts that the given hybrid property exists in the response. It accepts the same arguments as [`AssertableJson#has`](https://laravel.com/docs/9.x/http-tests#asserting-json-attribute-presence-and-absence).

### `assertMissingHybridProperty`

This method asserts that the given hybrid property is missing in the response. It accepts the same arguments as [`AssertableJson#missing`](https://laravel.com/docs/9.x/http-tests#asserting-json-attribute-presence-and-absence).

### `assertHybridView`

This method asserts that the view component of the hybrid response is equal to the given value. Additionally, if `hybridly.testing.ensure_views_exist` is set to `true`, which it is by default, it will ensure that the view component exists.

To ensure the view component's existence, the paths defined in `hybridly.testing.view_paths` will be used.

### `assertHybridVersion`

This method asserts that the `version` property of the hybrid response is equal to the given value.

### `assertHybridUrl`

This method asserts that the `url` property of the hybrid response is equal to the given value.

### `assertHybridPayload`

This method asserts that the property at the given path is equal to the given value. The path supports dot notation.

### `assertHybridDialog`

This method asserts that the given dialog exists, in addition to being able to asserts the correctness of its view component, its properties and its base URL.

**Usage**

```php
get('/users/create')
    ->assertHybridView('users.index')
    ->assertHybridUrl('http://localhost/users/create')
    ->assertHybridDialog(
        baseUrl: 'http://localhost/users',
        view: 'users.create',
        properties: [
            'teamId' => $teamId,
        ],
    );
```


### `assertHybrid`

This method accepts a callback that gets an `Hybridly\Testing\Assertable` instance as a parameter. 

`Assertable` extends [`Illuminate\Testing\Fluent\AssertableJson`](https://laravel.com/docs/9.x/http-tests#fluent-json-testing) and is initialized with the hybrid response's payload.

**Usage**

```php
use Hybridly\Testing\Assertable;

get('/')->assertHybrid(function (Assertable $hybrid) {
  // ...
});
```

### `assertNotHybrid`

This method simply asserts that the response is not hybrid.

### `hdd`

This method die and dumps the hybrid response's payload. If the response was not hybrid, it dumps the response's body instead.

It also accepts a `$path` parameter. If given, the hybrid property at the given path will be dumped instead.

## Global functions

### `partial_headers`

Generates headers for testing partial requests. Read more in the [functions documentation](./functions.md#partial-headers).
