# Partial reloads

## Overview

Partial reloads are repeated requests to the same page which purpose is to update or fetch specific properties, but not all of them. 

As an example, consider a page that includes a list of users, as well as an option to filter the users by their company. On the first request to the page, both the `users` and `companies` properties are passed to the page component.

However, on subsequent requests to the same page — maybe to filter the users —, you can request only the `users` property from the server, and not the `companies` one.

## Making partial reloads

Hybrid requests are "partial" when the `only` or `except` option is defined in the its options. 

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

It's often desirable to not evaluate a property until specifically needed — that is, included in a partial reload. To achieve this, you can use `Hybridly\Support\Partial`.

```php
use Hybridly\Support\Partial;

hybridly()->share([
  'filters' => Partial::make(fn () => $filters)
]);
```

:::info Alternative syntax
It is also possible to instanciate a partial-only property by calling `hybridly()->partial(fn () => ...)` or using `new Lazy(fn () => ...)`.
:::


## Lazy evaluation

It is possible to delay the evaluation of a property by using a closure. This property will still be evaluated on first page load and subsequent hybrid requests, but only when it needs to.

```php
hybridly()->share([
  'user' => fn () => auth()->user()
]);
```
