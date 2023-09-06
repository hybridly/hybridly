---
outline: deep
---

# `useProperty`

This function returns a property as a `Ref` given its dot-notated path. The path is typed, provided [TypeScript support for global properties](../../guide/global-properties.md#typescript-support) is set up properly.

This function is specifically useful to access global properties, but it can also access component properties.

| Related | [`setProperty`](./set-property.md), [`useProperties`](./use-properties.md) |
| ------- | -------------------------------------------------------------------------- |

## Usage

`useProperty` accepts a dot-notated path as its first parameter and returns a `ComputedRef` with the value at the given path.


```ts
const name = useProperty('security.user.full_name')

useHead({
  title: () => `${name.value}'s profile`
})
```

### Accessing page properties

While `useProperty` is primarily made for accessing typed, global properties, you may provide a custom generic type to opt-out of global type-checking if you need to access page-specific properties.

```ts
const posts = useProperty<App.Data.Post[]>('posts')
```
