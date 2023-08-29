# Authentication

## Overview

One of the benefits of Hybridly is that it acts like a classic monolithic application. There is no need for a token-based authentication system like the one provided by [Laravel Sanctum](https://laravel.com/docs/9.x/sanctum), or an advanced authentication system like OAuth.

Hybridly works best with session-based authentication systems, such as what Laravel provides by default. The demonstrations showcases [how authentication can be implemented](https://github.com/hybridly/demo/blob/main/app/Http/Controllers/Security/AuthenticationController.php).

## Sharing user data

Obtaining information regarding the currently logged-in user is usually done via [global properties](./global-properties.md). 

Ideally, avoid exposing the whole model — rather, select the properties you need and make them a data object.

```php
// app/Http/Middleware/HandleHybridRequests.php
public function share(): SharedData
{
    return SharedData::from([
        'security' => [
            'user' => UserData::optional(auth()->user()),
        ],
    ]);
}

// App\Data\UserData
final class UserData extends Data
{
    public function __construct(
        public readonly string $hashid,
        public readonly string $username,
        public readonly string $display_name,
        public readonly ?string $profile_picture_url,
        public readonly ?Carbon $identity_verified_at,
        public readonly string $email,
    ) {
    }
}
```

## Consuming user data

Hybridly does not provide any specific tool to read user data, but you may simply use `useProperty` to get the `user` property.

Optionally, you could write a wrapper around it:

```ts
// resources/composables/security.ts
export default function useSecurity() {
  const user = useProperty('security.user')
  const authenticated = computed(() => !!user.value)

  return {
    user,
    authenticated,
  }
}
```
