# Partial reloads

## Overview

Partial reloads are repeated requests to the same page which purpose is to update or fetch specific properties, but not all of them. 

As an example, consider a page that includes a list of users, as well as an option to filter the users by their company. On the first request to the page, both the `users` and `companies` properties are passed to the page component.

However, on subsequent requests to the same page—maybe to filter the users—you can request only the `users` property from the server, and not the `companies` one.

## Making partial reloads

Hybrid requests are "partial" when the [`only`](../api/router/options.md#only) or [`except`](../api/router/options.md#except) property is defined in its options. 

```ts
defineProps<{
  users: Paginator<App.Data.UserData>
  companies: Paginator<App.Data.CompanyData>
}>()

// ...

// Refreshes only the `users` property
router.reload({ only: ['users'] })

// ...or refresh everything except the `companies` property
router.reload({ except: ['companies'] })

// ...or refresh only a nested property
router.reload({ only: ['user.full_name'] })
```

## Persistent properties

[Persistent properties](./persistent-properties.md) are always returned, whether they are specifically excluded by `except` or implictly omitted by `only`.

Note that there is no way to un-persist a persistent property.

## Partial-only properties

It's often desirable to not evaluate a property until specifically needed—that is, included in a partial reload. To achieve this, you can use the [`partial`](../api/laravel/functions.md#partial) function:

```php
use function Hybridly\partial;

return hybridly('foo', [
  'filters' => partial(fn () => $filters)
]);
```

To fetch a partial-only property, use the [`only`](../api/router/options.md#only) option in a hybrid request:

```ts
router.reload({ only: ['filters'] })
```

## Deferred properties

Sometimes, for performance reason, it may be desirable to load the page first, and then load other properties which would have slowed down the initial page load.

You may use the [`deferred`](../api/laravel/functions.md#deferred) function to achieve this:

```php
use function Hybridly\deferred;

return hybridly('foo', [
	'slowProperty' => deferred(fn () => $fetchDataFromThirdParty())
])
```

This is functionally the same as manually making a partial reload in the `onMounted` hook:

```ts
defineProps<{
	slowProperty?: Data.ThirdPartyDataType
}>()

onMounted(() => {
	router.reload({ only: ['slowProperty'] })
})
```

Deferred properties are the exact same as [partial properties](#partial-only-properties), except they also trigger an automatic partial reload specifically for them after the page component has loaded.


## Lazy evaluation

It is possible to delay the evaluation of a property by using a closure. 

This property will still be evaluated on first page load and subsequent hybrid requests, but **only when the response is actually being sent**.

```php
return hybridly('foo', [
  'user' => fn () => auth()->user(),
]);
```
