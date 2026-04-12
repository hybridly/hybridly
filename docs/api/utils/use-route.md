---
outline: deep
---

# `useRoute`

<p class="preface">
This composable returns reactive utilities to work with the current route, including the reactive equivalents of <a href="../router/navigation.md#matches">router.matches</a> and <a href="../router/navigation.md#current">router.current</a>.
</p>

## Usage

`useRoute` doesn't accept any option.

```ts
const { isNavigating, current, matches } = useRoute()
```

## Utilities

### `isNavigating`

- **Type**: `Ref<boolean>`

Ref that determines whether a navigation is occuring.

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
