# Global properties

## Overview

In most applications, some data needs to be available globally. This is generally the case, for instance, of the logged-in user, but it could be anything else. To answer to this need, you may use global properties. 

Global properties are shared in every hybrid request — unless it's a [partial reload](./partial-reloads.md) — and can be accessed in the front-end using `useProperty` or `useProperties`.

## From the middleware

The most common way of defining global properties is to define them in the `HandleHybridRequests` middleware. It has a `share` method specifically for this purpose.

```php
public function share(): GlobalProperties
{
    return GlobalProperties::from([
        'security' => [
            'user' => UserData::optional(auth()->user()),
        ],
    ]);
}
```

Though this method can return any serializable property, such as a `Collection`, an array, a `Resource`, or anything `Arrayable`, a data object class is preferred in order to benefit from automatically-generated TypeScript definitions.

In the example above, `GlobalProperties` is a simple data object that takes a `SecurityData`, which accepts a `UserData`.

:::code-group
```php [app/Data/GlobalProperties.php]
final class GlobalProperties extends Data
{
    public function __construct(
        public readonly SecurityData $security,
    ) {
    }
}
```

```php [app/Data/SecurityData.php]
final class SecurityData extends Data
{
    public function __construct(
        public readonly ?UserData $user,
        public readonly int $characters,
    ) {
    }
}
```

```php [app/Data/UserData.php]
final class UserData extends Data
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $username,
        public readonly string $display_name,
        public readonly ?string $profile_picture_url,
        public readonly ?Carbon $identity_verified_at,
        public readonly string $email,
    ) {
    }
}
```
:::

## From anywhere

In the eventual case where sharing data outside the middleware is needed, it is possible through the `hybridly` function.

```php
hybridly()->share([
    'foo' => 'bar'
]);
```

While this is a useful escape hatch, it is not recommended. This way of sharing data being dynamic by nature, it is not possible to completely type it. 

If possible, consider using the middleware instead.

## Accessing global properties

The `useProperty` function provides typed dot-notation support for accessing global properties.

For instance, using the `user` property in the `security` array as shown in the earlier example would look like that:

```ts
const user = useProperty('security.user')
```

`useProperty` returns a `ref` that will be updated if the data is changed.
