# Hybridly

`Hybridly\Hybridly` is a singleton instance that contains convenience methods for common actions. It can be accessed by dependency injection or by service location using the [`hybridly()` global function](./functions.md#hybridly).

Note that most of the methods here are shortcuts to [namespaced function](./functions.md#namespaced-functions).

## `view`

Generates a `HybridResponse` with the given component and optional properties. The properties can be an array, an `Arrayable` or a data object.

> See [responses](../../guide/responses.md) for more details.

### Usage

```php
return hybridly()->view('users.show', [
  'user' => UserData::from($user)
]);
```

## `properties`

Generates a `HybridResponse` with the given properties. The properties can be an array, an `Arrayable` or a data object.

> See [responses](../../guide/responses.md#updating-properties) for more details.

### Usage

```php
return hybridly()->properties([
  'user' => UserData::from($user)
]);
```

## `base`

Makes the view a [dialog](../../guide/dialogs.md) and defines its base view. It takes a route name and its parameters as its arguments.

> See [dialogs](../../guide/dialogs.md) for more details.

### Usage

```php
return hybridly()
  ->view('users.edit', [
    'user' => UserData::from($user)
  ])
  ->base('users.show', $user);
```

## `external`
  
Generates a response for redirecting to an external website, or a non-hybrid view. 

This can also be used to redirect to a hybrid view when it is not known whether the current request is hybrid or not.

> See also: [`to_external_url`](./functions.md#to-external-url)
> 
> See [external redirects](../../guide/responses.md#external-redirects) for more details.

### Usage

```php
return hybridly()->external('https://google.com');
```

## `partial`

Creates a property that will only get evaluated and included when specifically requested through a partial reload.

> See also: [`partial`](./functions.md#partial)
> 
> See [partial reloads](../../guide/partial-reloads.md) for more details.

### Usage

```php
return hybridly('booking.estimates.show', [
  'booking' => BookingData::from($booking)
  'estimates' => hybridly()->partial(function () { // [!code focus:3]
    return SearchEstimates::run($booking);
  }),
]);
```

## `deferred`

Creates a partial property that will automatically be loaded in a subsequent partial reload when the page loads.

> See also: [`deferred`](./functions.md#deferred), [`partial`](./functions.md#partial)
> 
> See [deferred properties](../../guide/partial-reloads.md#deferred-properties) for more details.

### Usage

```php
return hybridly('booking.estimates.show', [
  'booking' => BookingData::from($booking)
  'estimates' => hybridly()->deferred(function () { // [!code focus:3]
    return SearchEstimates::run($booking);
  }),
]);
```

## `isHybrid`

> See also: [`is_hybrid`](./functions.md#is-hybrid)

Determines whether the current request is hybrid. Optionally, a `Illuminate\Http\Request` instance can be given instead of using the current request.

### Usage

```php
if (hybridly()->isHybrid()) {
  // ...
}
```

## `isPartial`

> See also: [`is_partial`](./functions.md#is-partial)

Determines whether the current request is a [partial reload](../../guide/partial-reloads.md). Optionally, a `Illuminate\Http\Request` instance can be given instead of using the current request.

### Usage

```php
if (hybridly()->isPartial()) {
  // ...
}
```

## `loadModule`

> See also: [architecture](../../guide/architecture.md#custom)

Loads views, layouts and components from the current directory. 

The layouts and components must be located in the `layouts` and `components` directories, respectively. Views are loaded recursively by default.

### Usage

```php
public function boot(Hybridly $hybridly): void
{
    $hybridly->loadModule(namespace: 'billing');
}
```

You may set the `recursive` argument to `false` to only load views in the `views` directory.

```php
$hybridly->loadModule(namespace: 'billing', recursive: false);
```

## `loadModuleFrom`

> See also: [architecture](../../guide/architecture.md#custom)

Loads views, layouts and components from the given directory. They must be located in the `views`, `layouts` and `components` directories, respectively.

### Usage

```php
public function boot(Hybridly $hybridly): void
{
    $hybridly->loadModuleFrom(
      directory: __DIR__,
      namespace: 'billing'
    );
}
```

## `loadModulesFrom`

> See also: [architecture](../../guide/architecture.md#custom)

Loads views, layouts and components from the subdirectories of the given directory.

### Usage

```php
public function boot(Hybridly $hybridly): void
{
    $hybridly->loadModulesFrom(resource_path('modules'));
}
```

## `loadViewsFrom`

> See also: [architecture](../../guide/architecture.md#custom)

Recursively loads Vue files in the given directory and registers them as views for the given namespace (or no namespace if left empty).

### Usage

```php
public function boot(Hybridly $hybridly): void
{
    $hybridly->loadViewsFrom(
      directory: __DIR__.'/views',
      namespace: 'billing',
    );
}
```

## `loadLayoutsFrom`

> See also: [architecture](../../guide/architecture.md#custom)

Loads Vue files in the given directory and registers them as layouts for the given namespace (or no namespace if left empty).

### Usage

```php
public function boot(Hybridly $hybridly): void
{
    $hybridly->loadLayoutsFrom(
      directory: __DIR__.'/layouts',
      namespace: 'billing',
    );
}
```


## `loadComponentsFrom`

> See also: [architecture](../../guide/architecture.md#custom)

Loads Vue files in the given directory and registers them as components for the given namespace (or no namespace if left empty). 

These components may be auto-imported by using their namespace and relative dot-notated path. 

### Usage

```php
// src/Billing/BillingServiceProvider.php
public function boot(Hybridly $hybridly): void
{
    $hybridly->loadComponentsFrom(
      directory: __DIR__.'/components',
      namespace: 'billing',
    );
}
```

Using the example above, the component `src/Billing/components/invoice/item.vue` can be auto-imported as `<billing-invoice-item />`.
