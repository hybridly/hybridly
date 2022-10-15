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

## Disabling default flashes

If you don't want flash notifications or prefer a custom implementation, you may disable this feature by adding the `shareFlashNotifications` to your middleware.

```php
class HandleHybridlyRequests extends Middleware
{
    protected bool $shareFlashNotifications = false;

    // ...
}
```
