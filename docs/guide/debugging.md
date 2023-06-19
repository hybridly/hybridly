# Debugging

## Using Vue Devtools

Hybridly comes with a built-in Vue plugin that integrates with Vue Devtools. You may read more about it on the [dedicated documentation](./devtools.md).

## In tests

During tests, you may use the `hdd` macro on `TestResponse` instances.

```php
test('guests can see the login page', function () {
    get('/login')
        ->hdd()
        ->assertOk()
        ->assertHybrid();
});
```

When using `hdd` on non-hybrid responses, the response's body will be shown instead.

<img
  src="../assets/hdd.webp"
  alt="hdd macro"
  class="mt-8"
/>

## Using Ray

If you are using [Ray](https://spatie.be/docs/ray/v1/introduction) by Spatie, the `showHybridRequests` macro will automatically be registered.

```php
ray()->showHybridRequests();
```

This may be useful, for instance, to investigate which properties are sent to the front-end.

<img
  src="../assets/ray-debugging.webp"
  alt="Ray debugging"
/>

To stop showing hybrid responses, you may also call `stopShowingHybridRequests`.
