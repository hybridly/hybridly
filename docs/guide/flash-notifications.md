# Flash notifications

## Overview

By default, flash notifications are [globally shared](./global-properties.md) and [persisted](./persistent-properties.md). You can access them using `useProperty` or `useProperties`.

```ts
const properties = useProperties()
// properties.flash

const flash = useProperty('flash')
// flash.value.error
// flash.value.info
// flash.value.success
// flash.value.warning
```

Just like with Blade, flash notifications are only available for the current request, after which they are discarded.

As a reminder, you can create a flash notification by using `response()->with()` or `session()->flash()`:

```php
return back()->with('success', 'Lorem ipsum dolor sit amet.');
```

## TypeScript

By default, flash notifications are not typed, so using `useProperty` to access them will result in a type error. You may use a data object to provide their definitions:

```php
final class FlashBagData
{
    public function __construct(
      public readonly ?string $success,
      public readonly ?string $danger,
      public readonly ?string $warning,
      public readonly ?string $info,
    ) {}
}
```

Do not forget to include this data object in the one that describes your global properties:

```php
final class GlobalProperties extends Data
{
    public function __construct(
        public readonly SecurityData $security,
        public readonly ?FlashBagData $flash,
    ) {
    }
}
```

## Disabling default flashes

If you don't want flash notifications or prefer a custom implementation, you may disable this feature by adding the `shareFlashNotifications` to your middleware.

```php
class HandleHybridRequests extends Middleware
{
    protected bool $shareFlashNotifications = false;

    // ...
}
```
