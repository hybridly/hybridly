---
outline: 'deep'
---

# Architecture configuration

## Overview

By default, Hybridly will load views, layouts and components from the `views`, `layouts` and `components` directories in `resources`.

This behavior is suitable for most applications, but Hybridly supports any kind of architecture.

## Default architecture

By default, Hybridly uses the following file structure:

```
resources/
├── application/
│   ├── main.ts
│   └── root.blade.php
├── layouts/
│   └── default.vue
├── views/
│   ├── index.vue
│   └── security/
│       ├── register.vue
│       └── login.vue
├── utils/
└── composables/
```

- View components are located in `resources/views` and may be nested.
- Layout components are located in `resources/layouts`.
- Utility functions and composables (`*.ts`) are auto-imported.
- The base Blade template is located at `resources/application/root.blade.php`.

This convention is generally good, but you may use a module-based or completely custom architecture if your application needs that.

## Other architectures

If the default architecture isn't suited for your application, you may disable it by setting the `architecture.load_default_module` configuration option to `false` in `config/hybridly.php`.

Instead, you may use a modular architecture, or a a completely custom one.

### Modular

When using a modular architecture, views and layouts will be loaded from the `views` and `layouts` subdirectories, while TypeScript files will be auto-imported from `utils` and `composables`. Components will also be auto-imported using hyphens.

You may use the `loadModulesFrom` method to load modules in `resources/domains` or any other directory of your choice:

:::code-group
```php [AppServiceProvider.php]
final class AppServiceProvider extends ServiceProvider
{
    public function boot(Hybridly $hybridly): void
    {
        $hybridly->loadModulesFrom(base_path('resources/domains'));
    }
}
```
``` [Example architecture]
resources/
├── applications/
│   ├── main.ts
│   └── root.blade.php
└── domains/ // [!code hl]
    └── authentication/
        ├── layouts/
        │   └── default.vue
        ├── views/
        │   ├── login.vue
        │   └── register.vue
        ├── components/
        │   ├── login-container.vue
        │   └── login-button.vue
        ├── utils/
        └── composables/
```
:::


### Custom

If you need more flexibility, you may load views, layouts, components or modules using the more advanced architecture API.

Generally, this is done in the `boot` method of a service provider:

:::code-group
```php [BillingServiceProvider.php]
final class BillingServiceProvider extends ServiceProvider
{
    public function boot(Hybridly $hybridly): void
    {
        $hybridly->loadModuleFrom(
          directory: __DIR__,
          namespace: 'billing'
        );
    }
}
```
``` [Example architecture]
src/
└── Billing/
    ├── BillingServiceProvider.php // [!code hl]
    ├── Actions/
    │   ├── CreateInvoice.php
    │   └── ProcessPayment.php
    ├── Models/
    │   └── Invoice.php
    ├── views/  // [!code hl:8]
    │   ├── index.vue
    │   └── invoices/
    │       ├── index.vue
    │       ├── create.vue
    │       └── edit.vue
    └── components/
        └── invoice.vue
```
:::

Alternatively, you may register individual views, layouts or components:

```php
public function boot(Hybridly $hybridly): void
{
    // Loads Vue files as views inside the given directory
    // and registers them using the given namespace
    $hybridly->loadViewsFrom($directory, $namespace);

    // Loads Vue files as layouts inside the given directory
    // and registers them using the given namespace
    $hybridly->loadLayoutsFrom($directory, $namespace);

    // Loads Vue files as components inside the given directory
    // and registers them using the given namespace
    $hybridly->loadComponentsFrom($directory, $namespace);
}
```

You can read about the available methods in the [API documentation](../api/laravel/hybridly.md#loadmodule).

## Namespaces

When loading modules using `loadModulesFrom` or `loadModuleFrom`, the views, layouts and components will be namespaced.

Views and layouts can be referred to using the `module-name::path.to.view` syntax. 

Components may be auto-imported by concatenating the module name and the component name with hyphens.

&nbsp;

:::code-group
```php [Views]
return hybridly('authentication::login');
```
```html [Layouts and components]
<template layout="authentication::default">
	<!-- ... -->
	<authentication-login-container>
		<!-- ... -->
	</authentication-login-container>
</template>
```
``` [Example architecture]
resources/
├── applications/
│   ├── main.ts
│   └── root.blade.php
└── domains/
    └── authentication/
        ├── layouts/
        │   └── default.vue // [!code hl]
        ├── views/
        │   ├── login.vue // [!code hl]
        │   └── register.vue
        ├── components/
        │   ├── login-container.vue // [!code hl]
        │   └── login-button.vue
        ├── utils/
        └── composables/
```
:::
