# Laravel configuration

## Overview

Like most Laravel packages, Hybridly has a few settings available for modification in `config/hybridly.php`.

The defaults are generally good, but you may change them to your liking.

Run the following command to publish the `config/hybridly.php` file.

```bash
php artisan vendor:publish --tag=hybridly-config
```

## Route filters

The built-in [route support](../api/utils/route.md) requires Hybridly to share available routes to the front-end.

In most cases, this is not an issue, but in some situations you may want to restrict which routes are directly exposed in order to reduce the surface of a potential attack.

### Excluding routes

You may add filters to `router.exclude`. These filters are matched against the final URI, and support wildcards:

```php
'router' => [
    'exclude' => [
        'admin*', // [!code ++]
    ],
],
```

### Including vendors

By default, routes from vendor packages are not available to the front-end. You may include some by specifying the `vendor/package` identifier in the `router.allowed_vendors` option:

```php
'router' => [
    'allowed_vendors' => [
        'laravel/fortify', // [!code ++]
    ],
],
```

## Architecture

Hybridly is flexible when it comes to architecturing your application. 

It offers two presets: the default one, a [single-level architecture](../guide/architecture.md#single-level) similar to the architecture that Laravel promotes, and a [modular](../guide/architecture.md#modular) one.

## Specifying a preset

You may specify the preset you want to use in the `architecture.preset` option. Possible values are `default` and `modules`.

```php
'architecture' => [  // [!code focus:2]
    'preset' => 'default', // [!code hl]
    'root' => 'resources', 
    'eager_load_views' => true,
],  // [!code focus]
```

If you want to use a custom architecture, you may disable the presets by setting that option to `false`. 

To learn how to define your own architecture, read the [corresponding documentation](../guide/architecture.html#custom).

## Updating the root directory

By default, Laravel comes with the `resources` directory, that is used by many external packages and Laravel itself. 

Hybridly also uses `resources` for its default architecture, but you may chose to use a separate directory if you want. To do that, update the `architecture.root` option:

```php
'architecture' => [  // [!code focus]
    'preset' => 'default',
    'root' => 'frontend', // [!code focus] 
    'eager_load_views' => true,
],  // [!code focus]
```

This directory is used by the `@` import alias and for some of the integrations, like auto-imports, icons, or for loading the `<root>/application/main.ts` entrypoint.

## Eager-loading views

By default, Hybridly will eager-load view components, which means that users will have to load all views at once when accessing the application.

This is a good default, but if your application has a lot of views, you may need to disable eager-loading, so views can be lazy-loaded as needed by your users.

To do that, simply set `architecture.eager_load_views` to `false`.

```php
'architecture' => [  // [!code focus]
    'preset' => 'default',
    'root' => 'frontend',
    'eager_load_views' => false, // [!code focus] 
],  // [!code focus]
```
