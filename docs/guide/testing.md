# Testing

## Overview

The most basic way of testing hybrid applications is to ensure the responses of your endpoints return what you expect. This is sometimes referred to as endpoint testing.

Hybridly provides macros to reduce the boilerplate needed for common tests. For instance, you may assert that an endpoint returned a specific view or contain a specific property:

```php
test('users can see the index page', function () {
    get('/')
        ->assertOk()
        ->assertHybrid()
        ->assertHybridView('index')
        ->assertMissingHybridProperty('security.user');
});
```

Learn about available testing methods in their [API documentation](../api/laravel/index.md#testing).

:::info Disable Vite in tests
It is highly recommend to [disable Vite in tests](https://laravel.com/docs/9.x/vite#disabling-vite-in-tests), otherwise it will expect built assets or the development server to be running, which is highly inconvenient.
:::

## End-to-end tests

To complement your tests, you may introduce tests that automate browsers to simulate real user-centric scenarios. this is known as end-to-end testing.

To achieve this, you may use tools like [Laravel Dusk](https://laravel.com/docs/8.x/dusk), [Cypress](https://www.cypress.io/) or [Playwright](https://playwright.dev/).

While end-to-end tests are slower, they are resilient and provide a lot of confidence that your application is working properly.
