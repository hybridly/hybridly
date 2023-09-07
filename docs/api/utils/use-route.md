---
outline: deep
---

# `useRoute`

This composable returns reactive utilities to work with the current route. These utilities are the reactive equivalents of [`router.matches`](../router/utils.md#matches) and [`router.current`](../router/utils.md#current).

| Related | [Routing](../../guide/routing.md), [`route`](route.md) |
| ------- | --------------------------------------------------------------- |

## Usage

`useRoute` doesn't accept any option. 

```ts
const { current, matches } = useRoute()
```

## Utilities

### `current`

- **Type**: `Ref<string | undefined>`

Ref that contains the current route name. It may be `undefined` if the current route has no defined name.

### `matches`

- **Type**: `(name: MaybeRef<string>, parameters?: RouteParameters) => boolean`

Determines whether the given `name` matches the current route name. Providing parameters would also ensure the current route parameters match.

```ts
matches('tenant.*')
matches('profile', { user: currentUserId })
```
