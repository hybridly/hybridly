# Global properties

<p class="preface">
Learn how to share data globally across your application in a type-safe way, and how to access it in your views.
</p>

## Overview

In most applications, some data needs to be available globally. This is generally the case, for instance, of the logged-in user, for navigation data or for flash notifications.

You may achieve this by sharing properties globally, which may be accessed in the front-end using [`useProperty`](../api/utils/use-property.md) or [`useProperties`](../api/utils/use-properties.md).

## From a middleware

The idiomatic way of defining global properties is to create a dedicated middleware and to share properties from there.

```php
final readonly class ShareNavigation
{
    public function __construct(
        private Hybridly $hybridly,
        private NavigationBuilder $navigation,
    ) {}

    public function __invoke(Request $request, Closure $next): Response
    {
        $this->hybridly->persist(['navigation']);
        $this->hybridly->share('navigation', $this->navigation->getSidebarItems());

        return $next($request);
    }
}
```

It is generally a good idea to create a TypeScript declaration file to inform TypeScript about the properties that are shared globally. For instance, the declaration file for the example above could look like that:

```ts
import 'hybridly'

declare module 'hybridly' {
	export interface GlobalHybridlyProperties {
		navigation: App.Navigation.SharedNavigationItem[]
	}
}

export {}
```

This makes [`useProperty`](../api/utils/use-property.md) type-safe and provides your editor with autocompletion.

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

The [`useProperty`](../api/utils/use-property.md) function provides typed dot-notation support for accessing global properties.

For instance, using the `user` property in the `security` array as shown in the earlier example would look like that:

```ts
const user = useProperty('security.user')
```

[`useProperty`](../api/utils/use-property.md) returns a `ref` that will be updated if the data is changed.
