# `useProperty`

This composable returns a reactive property given its dot-notated path. The path is typed, provided [TypeScript support for global properties](../../guide/global-properties.md#typescript-support) is set up properly.

This is the preferred method to access global properties, but it can also access component properties.

## Usage

```ts
function useProperty<
	T = Path<GlobalHybridlyProperties>,
  Fallback = any
>(
	path: T,
	fallback?: Fallback,
): ComputedRef<T>
```

`useProperty` accepts a dot-notated path as its first parameter, and a fallback value as its second. It returns a `ComputedRef` with the value at the given path, or the fallback.

## Example

```ts
<script setup lang="ts">
const name = useProperty('security.user.full_name') // [!code focus:5]

useHead({
  title: () => `${name.value}'s profile`
})
</script>
```
