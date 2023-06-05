# Laravel configuration

## Overview

Like most Laravel packages, Hybridly has a few settings available for modification in `config/hybridly.php`. 

The defaults are generally good, but you may change them to your liking.

## Route filters

The built-in [route support](../api/utils/route.md) requires Hybridly to share available routes to the front-end. 

In most cases, this is not an issue, but in some situations you may want to restrict which routes are directly exposed in order to reduce the surface of a potential attack.

### Excluding routes

You may add filters to `router.excludes`. These filters are matched against the final URI, and support wildcards:

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
