---
outline: "deep"
---

# Architecture configuration

<p class="preface">
Learn how to configure Hybridly to find view and layout files anywhere in your codebase.
</p>

## Overview

By default, Hybridly expects view and layout files to be stored somewhere in the `resources` directory or its subdirectories.

However, storing view files far apart from their related code can be inconvenient. For instance, if you have a `Billing` domain, you might want to store its views and layouts in `src/Billing` instead of `resources`.

For this reason, Hybridly provides the ability to configure where it looks for view and layout files.

## Default architecture

By default, Hybridly uses the following file structure:

```text
resources/
├── main.ts
├── root.blade.php
├── index.view.vue
├── security/
│  ├── register.view.vue
│  └── login.view.vue
└── default.layout.vue
```

As you can see, the front-end lives in `resources`, including the root Blade layout and the `main.ts` entrypoint.

There is no `resources/views` or `resources/layouts` subdirectory, as this would affect the identifiers for these components.

## Modular architecture

If the default architecture is not suitable, you may update the `architecture` configuration in `config/hybridly.php`:

```php
'architecture' => [
    // ...
    'root_directory' => 'resources', // [!code --]
    'component_loader' => ResourcesComponentLoader::class, // [!code --]
    'root_directory' => 'app', // [!code ++]
    'component_loader' => ModulesComponentLoader::class, // [!code ++]
],
```

- The `component_loader` option accepts a class name that implements the `ComponentLoader` interface. This class is responsible for finding view and layout files in the file system and registering them.

- The `root_directory` option defines the directory in which Hybridly expects to find the `main.ts` and `root.blade.php` files.

### Example

Using the configuration above, you would typically organize your code by modules or vertical slices in your main namespace:

```text
app/
├── main.ts
├── root.blade.php
├── default.layout.vue
└── Authentication/
    ├── User.php
    ├── RegisterUserController.php
    └── register.view.vue
```

In this example, the `register.view.vue` file would be identifier by `authentication.register`.

## Configuration reference

The following options are available for the `architecture` configuration in `config/hybridly.php`:

### `root_directory`

Defines the directory in which Hybridly expects to find the `main.ts` and `root.blade.php` files.

### `component_loader`

Defines the class responsible for finding view and layout files in the file system and registering them.

By default, it is `ResourcesComponentLoader`, which looks for files in the `resources` directory.

Another available option is `ModulesComponentLoader`, which looks for files in the `src` directory.

### `eager_load_views`

Defines whether to enable code-splitting. When enabled, all view and layout files will be loaded on the first request. This is a good default for small to medium applications.

### `generate_absolute_urls`

Defines whether to generate absolute URLs when using the [`route`](../api/utils/route.md) util.

### `entrypoint`

Defines the name of the entrypoint file. By default, it is `main.ts`.

### `root_view`

Defines the name of the root view. By default, it is `root`.
