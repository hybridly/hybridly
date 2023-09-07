# `useBackForward`

This composable returns two functions, `onBackForward` and `reloadOnBackForward`, that register callbacks which will be called after a back-forward navigation.

A back-forward navigation is a navigation that uses the "back" or "forward" browser functionality.

## Usage

```ts
function useBackForward(): {
  onBackForward: (fn: BackForwardCallback) => void
  reloadOnBackForward: (options: HybridRequestOptions) => void
}
```

Calling `useBackForward` creates a scope in which callbacks are registered. It returns `onBackForward` and `reloadOnBackForward`, which add a callback to the scope when called.

## Examples

The following example reloads the page when a back or forward browser navigation is made, in order to refresh potentially-stale data.

```vue
<script setup lang="ts">
const { reloadOnBackForward } = useBackForward() // [!code focus]

defineProps<{
  users: Paginator<App.Data.UserData>
}>()

reloadOnBackForward({  // [!code focus:3]
  only: ['users']
})
</script>
```

The following example calls the defined callback when a back or forward browser navigation is made.

```vue
<script setup lang="ts">
const { onBackForward } = useBackForward() // [!code focus]

onBackForward(({ url }) => {  // [!code focus:3]
  console.log(`Back-forward navigation made to ${url}`)
})
</script>
```
