# `useProperty`

This composable returns a reactive property given its dot-notated path. The path is typed, provided [TypeScript support for global properties](../../guide/global-properties.md#typescript-support) is set up properly.

This is the preferred method to access global properties, but it can also access component properties.

## Usage

```ts
function useProperty<
	Override = never,
  T = GlobalHybridlyProperties,
>(
	path: T | string,
): ComputedRef<T | Override>
```

`useProperty` accepts a dot-notated path as its first parameter and returns a `ComputedRef` with the value at the given path.

## Example

```ts
<script setup lang="ts">
const name = useProperty('security.user.full_name') // [!code focus:5]

useHead({
  title: () => `${name.value}'s profile`
})
</script>
```

## Accessing page properties

While `useProperty` is primarily made for accessing typed, global properties, you may provide a custom generic type to opt-out of global type-checking if you need to access page-specific properties.

```ts
const posts = useProperty<App.Data.Post[]>('posts')
```
