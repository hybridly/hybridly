---
outline: 'deep'
---

# Architecture configuration

## Overview

Properly architecturing an application can be difficult. Hybridly supports any kind of architecture, but provides two presets that will work for most applications.

The default one is a single-level structure, and the second one is a module-based architecture.

## Single-level

By default, Hybridly uses the following structure:

```
resources/
├── application/
│   ├── main.ts
│   └── root.blade.php
├── layouts/
│   └── default.vue
├── pages/
│   ├── index.vue
│   └── security/
│       ├── register.vue
│       └── login.vue
├── utils/
└── composables/
```

- Page components are located in `resources/pages` and may be nested.
- Layout components are located in `resources/layouts`.
- Util functions and composables are auto-imported.
- The base Blade template is located at `resources/application/root.blade.php`.

This convention is generally good, but you may use a domain-based or completely custom architecture if your application needs that.

## Modular

You may use the predefined modular architecture using the `architecture.preset` option, or you may manually register modules as you see fit. 

When the `architecture.preset` option is set to `modules`, directories in `resources/domains` will be considered as modules. 

Pages and layouts will be loaded from the `pages` and `layouts` subdirectories, while TypeScript files will be auto-imported from `utils` and `composables`.

```
resources/
├── applications/
│   ├── main.ts
│   └── root.blade.ts
└── domains/
    └── security/
        ├── layouts/
        │   └── default.vue
        ├── pages/
        │   ├── login.vue
        │   └── register.vue
        ├── utils/
        └── composables/
```

Note that you may change the name of the `domains` directory by updating the `architecture.domains_directory` setting.

## Custom

If the above presets do not work well for your application, you may set `architecture.preset` to `false` and manually register views, layouts, components, or modules using the provided API.

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
    ├── pages/  // [!code hl:8]
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

You can read about the available methods in the [API documentation](../api/laravel/hybridly.md#loadmodulefrom).
