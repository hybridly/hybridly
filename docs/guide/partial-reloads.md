# Partial reloads

## Overview

Partial reloads are repeated visits to the same page which purpose is to update specific data, but not all of it. 

As an example, consider a page that includes a list of users, as well as an option to filter the users by their company. On the first request to the page, both the `users` and `companies` data is passed to the page component.

However, on subsequent visits to the same page — maybe to filter the users —, you can request only the `users` data from the server, and not the `companies` data.

## Making partial visits

Partial visits are made when the `only` or `except` option is defined in the visit's option. Note that it is not possible to use `only` and `except` at the same time.

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
```

If needed, dot notation support is also supported.

```ts
defineProps<{
  user: App.Data.UserData
}>()

router.reload({ only: ['user.full_name'] })
router.reload({ except: ['user.full_name'] })
```

## Persistent properties

[Persistent properties](./persistent-properties.md) are always returned, whether they are specifically excluded by `except` or implictly omitted by `only`.

Note that there is no way to un-persist a persistent property.
