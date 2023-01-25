# Hybridly

`Hybridly\Hybridly` is a singleton instance that contains shortcuts for common actions.

## `view`

Generates a `HybridResponse` with the given component and optional properties. The properties can be an array, an `Arrayable` or a data object.

> See [responses](../../guide/responses.md) for more details.

### Usage

```php
return hybridly()->view('users.show', [
  'user' => UserData::from($user)
]);
```

## `base`

Makes the view a [dialog](../../guide/dialogs.md) and defines its base page. It takes a route name and its parameters as its arguments.

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
  
Generates a response for redirecting to an external website, or a non-hybrid page. 

This can also be used to redirect to a hybrid page when it is not known whether the current request is hybrid or not.

> See [external redirects](../../guide/responses.md#external-redirects) for more details.

### Usage

```php
return hybridly()->external('https://google.com');
```

## `partial`

Creates a property that will only get evaluated and included when specifically requested through a partial reload.

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

## `isHybrid`

> See also: [`is_hybrid`](./global-functions.md#is_hybrid)

Determines whether the current request is hybrid. If a `Illuminate\Http\Request` instance is given, uses this instance instead of the current request.

### Usage

```php
if (hybridly()->isHybrid()) {
  // ...
}
```
