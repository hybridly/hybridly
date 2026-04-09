---
outline: [2, 3]
---

# Partial reloads

<p class="preface">
Learn how to efficiently and transparently fetch and reload data on the same page without relying on typical AJAX requests.
</p>

## Overview

Partial reloads are repeated requests to the same page which purpose is to update or fetch specific properties, but not all of them.

As an example, consider a page that includes a list of users, as well as an option to filter the users by their company. On the first request to the page, both the `users` and `companies` properties are passed to the view component.

However, on subsequent requests to the same page—maybe to filter the users—you can request only the `users` property from the server, and not the `companies` one.

## Making partial reloads

Partial reloads only work for requests made for the same page, as their purpose is to update the current properties or fetch new ones.

Hybrid requests are "partial" when the [`only`](../api/router/options.md#only) or [`except`](../api/router/options.md#except) option is used:

```ts
// Refreshes only the `users` property
router.reload({ only: ['users'] })

// ...or refresh everything except the `companies` property
router.reload({ except: ['companies'] })

// ...or refresh only a nested property
router.reload({ only: ['user.full_name'] })
```

## Persistent properties

When using partial reloads, any non-specified property will not be sent back to the front-end.

This is the desired behavior, except when it comes to data that is needed no matter the context. For instance, you may initiate a partial reload that triggers a flash message, which needs to be returned with the partial reload's response even if the `flash` property was not included in its `only` parameter.

To solve this problem, persistent properties will always be sent, even in partial reloads.

### Persisting properties

To mark a property as persisted, you may use the `persist()` method on the `Hybridly` instance:

```php
hybridly()->persist('toasts');
```

This is typically done in a dedicated middleware, or sometimes in a service provider.

#### Example

In the following example, the `ShareToasts` is included in every request. When the session contains `toasts` instances, they are shared to the client.

Since the middleware also persist the `toasts` property, it will always be included, even in partial requests.

:::code-group

```php [src/Toasts/ShareToasts.php]
final readonly class ShareToasts
{
    public function __construct(
        private Hybridly $hybridly,
    ) {}

    public function __invoke(Request $request, Closure $next): Response
    {
        $toasts = [];

        foreach ($request->session()->get('toasts', default: []) as $toast) {
            if (! $toast instanceof Toast) {
                continue;
            }

            $toasts[] = $toast;
        }

        $this->hybridly->persist('toasts');
        $this->hybridly->share('toasts', $toasts);

        return $next($request);
    }
}
```

```ts [src/Toasts/toasts.d.ts]
import 'hybridly'

declare module 'hybridly' {
	export interface GlobalHybridlyProperties {
		toasts: App.Toasts.Toast[]
	}
}

export {}
```

:::

## On-demand properties

In situations where some data is not needed when the page initially loads, you may use on-demand properties.

These properties are not evaluated until specifically required by a partial request.

To achieve this, you can use the [`on_demand`](../api/laravel/functions.md#on_demand) function:

```php
use function Hybridly\on_demand;

return views('users.show', [
  'users' => $users,
  'companies' => on_demand(fn () => Companies::query()->all()),
]);
```

## Deferred properties

For performance reasons, it may be desirable to load the page first, and then load other properties which would have slowed down the initial page load. This can increase the perceived performance of the page and improve the user experience.

When rendering a view with a [`deferred`](../api/laravel/functions.md#deferred) property, it will not be sent on the initial page load. When the page has loaded, Hybridly will automatically trigger partial reloads for those properties.

```php
use function Hybridly\deferred;

return view('users.show', [
  'companies' => deferred(fn () => Company::query()->all())
])
```

This is functionally the same as manually making a partial reload in the `onMounted` hook:

```ts
defineProps<{
	companies?: App.Companies.Company[]
}>()

onMounted(() => {
	router.reload({ only: ['companies'] })
})
```

### Grouping requests

By default, all deferred properties are fetched in a single request. When deferring multiple properties that take time to load, it can be faster to parallelize their loading by triggering multiple requests, one for each property.

```php
return view('users.show', [
  'chart' => deferred(fn () => $chart),
  'stats' => deferred(fn () => $stats, group: 'stats'),
])
```

## Mergeable properties

By default, incoming properties replace the previous value on the front-end. You may instruct Hybridly to merge the new value into its existing one instead by using the [`merge`](../api/laravel/functions.md#merge) function:

```php
use function Hybridly\merge;

return view('feed.index', [
  'items' => merge(fn () => FeedItemData::collection($items)),
]);
```

Note that `merge` also accepts an array.

### Prepending

By default, merging appends new items to the existing array. You can prepend new items instead by passing `prepend: true`:

```php
return view('feed.index', [
  'items' => merge(
      value: fn () => FeedItemData::collection($items),
      prepend: true
  ),
]);
```

### Deduplicating

If some values are meant to be unique, you may deduplicate items by specifying a unique key with the `uniqueBy` option:

```php
return view('feed.index', [
  'items' => merge(
      value: fn () => FeedItemData::collection($items),
      uniqueBy: 'meta.id'
  ),
]);
```

### Clearing merged properties

You may instruct the server to reset the property instead of merging it. This is useful, for instance, when changing filters.

```ts
router.reload({ reset: ['items'] })
```

## Lazy evaluation

It is possible to delay the evaluation of a property by using a closure.

This property will still be evaluated on first page load and subsequent hybrid requests, but **only when the response is actually being sent**. The main

```php
hybridly()->share([
  'user' => fn () => [
      'email' => Auth::user()->email,
  ],
]);
```
