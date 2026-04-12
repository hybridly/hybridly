# Authentication

<p class="preface">
Learn how to work with authentication and how to share user data across the application.
</p>

## Overview

One of the benefits of Hybridly is that it acts like a classic monolithic application. There is no need for a token-based authentication system like the one provided by [Laravel Sanctum](https://laravel.com/docs/9.x/sanctum), or an advanced authentication system like OAuth.

Hybridly works best with session-based authentication systems, such as what Laravel provides by default.

## Sharing user data

In most application requiring authentication, you need to share some information regarding the currently logged-in user across the application. The [global properties documentation](./global-properties.md) explains how to achieve this by using a middleware.

:::code-group

```php [app/Users/ShareUserData.php]
use Hybridly\Hybridly;
use Illuminate\Auth\AuthManager;

final readonly class ShareUserData
{
    public function __construct(
        private Hybridly $hybridly,
        private AuthManager $auth,
    ) {}

    public function __invoke(Request $request, Closure $next): Response
    {
        if (! $this->auth->check()) {
            return $next($request);
        }

        $this->hybridly->persist('user');
        $this->hybridly->share(new UserData(
            name: $user->name,
            email: $user->email,
        ));

        return $next($request);
    }
}
```

:::

In this example, the `ShareUserData` middleware shares a `user` property with the client containing the name and email of the currently logged-in user.

The shape of the `user` object is defined by the `UserData` data object, which types can be [automatically generated](./typescript.md).

On the front-end, you may use [`useProperty`](../api/utils/use-property.md) to read the `user` property and get the user data.

```vue [resources/default.layout.vue]
<script setup lang="ts">
import { useProperty } from 'hybridly/vue'

const user = useProperty('user')
</script>

<template>
	<main>
		Hello, {{ user.name }}!
		<slot />
	</main>
</template>
```
