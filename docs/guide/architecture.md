---
outline: 'deep'
---

# Architecture configuration

## Overview

Hybridly resolves view and layout files through its components resolver.

By default, it loads files from your configured `architecture.root_directory` (usually `resources`) and expects:

- views named with `.view.<ext>`
- layouts named with `.layout.<ext>`

Supported extensions come from `architecture.extensions`.

## Default architecture

By default, Hybridly uses this structure:

```text
resources/
├── application/
│   ├── main.ts
│   └── root.blade.php
├── views/
│   ├── index.view.vue
│   └── security/
│       ├── register.view.vue
│       └── login.view.vue
└── layouts/
    └── default.layout.vue
```

If `architecture.load_default_module` is enabled, Hybridly registers this root directory as the `default` namespace.

## Custom architecture

If the default layout is not suitable, disable it in `config/hybridly.php`:

```php
'architecture' => [
    'load_default_module' => false,
]
```

Then register your own directories in a service provider.

### Load a module directory

`loadModuleFrom` recursively registers both views and layouts from a directory.

```php
use Hybridly\Hybridly;
use Illuminate\Support\ServiceProvider;

final class BillingServiceProvider extends ServiceProvider
{
    public function boot(Hybridly $hybridly): void
    {
        $hybridly->loadModuleFrom(
            directory: base_path('src/Billing'),
            namespace: 'billing',
        );
    }
}
```

### Load views and layouts separately

```php
public function boot(Hybridly $hybridly): void
{
    $hybridly->loadViewsFrom(
        directory: resource_path('domains/billing/views'),
        namespace: 'billing',
    );

    $hybridly->loadLayoutsFrom(
        directory: resource_path('domains/billing/layouts'),
        namespace: 'billing',
    );
}
```

### Register explicit files

For full control, register specific files using `addView` and `addLayout`.

```php
public function boot(Hybridly $hybridly): void
{
    $hybridly->addView(
        path: resource_path('domains/billing/views/invoices/show.view.vue'),
        namespace: 'billing',
        identifier: 'billing::invoices.show',
    );

    $hybridly->addLayout(
        path: resource_path('domains/billing/layouts/default.layout.vue'),
        namespace: 'billing',
        identifier: 'billing::default',
    );
}
```

## Namespaces

When you load files with a namespace, use the `namespace::identifier` format.

```php
return hybridly()->view('billing::invoices.show');
```

```vue
<template layout="billing::default">
	<h1>Invoice</h1>
</template>
```

## Identifier generation

Identifiers are kebab-cased and path-based.

- `views/MyPage.view.vue` becomes `my-page` in the `default` namespace.
- `views/admin/Users.view.vue` becomes `admin.users` in the `default` namespace.

For custom behavior, you can provide your own identifier generator with `setIdentifierGenerator`.
