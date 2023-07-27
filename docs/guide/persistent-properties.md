# Persistent properties

## Overview

When using [partial reloads](./partial-reloads.md), any non-specified property will not be sent back to the front-end. 

This is the desired behavior, except when it comes to data that's needed no matter the context. For instance, you don't want to not update the current user's data or skip flash messages when doing a partial reload.

To solve this problem, persistent properties will always be sent, even in partial reloads.

## Persisting properties

To mark a property as persisted, the `hybridly()->persist()` method may be used. In the following example, any top-level property named `current_date` will be persisted.

```php
hybridly()->persist('current_date');
```

However, since persistent properties are usually defined in the `HandleHybridRequests` middleware, a convenient `$persistent` property is available.

```php
class HandleHybridRequests extends Middleware
{
    protected array $persistent = [
      'security.user'
    ];
    
    public function share()
    {
        return GlobalProperties::from([
            'security' => [
                'user' => UserData::optional(auth()->user()),
            ],
        ]);
    }
}
```

## Flashes and validation errors

By default, flash notifications and validation errors are marked as persistent, so users can't miss important information during partial reloads.

This cannot be undone unless [turning off flash notification](./flash-notifications.md#disabling-default-flashes) and [validation error](./validation.md) sharing. However, if you need to turn off persistent flash and error data, it most certainly means you have custom sharing logic for them.
