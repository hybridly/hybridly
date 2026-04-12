# `useBackForward`

<p class="preface">
This composable provides <code>onBackForward</code> and <code>reloadOnBackForward</code> helpers to react to browser back-forward navigations.
</p>

## Usage

```ts
interface UseBackForwardOptions {
	/**
	 * Calls `reloadOnBackForward` immediately.
	 */
	reload: boolean | HybridRequestOptions
}

function useBackForward(options?: UseBackForwardOptions): {
	onBackForward: (fn: BackForwardCallback) => void
	reloadOnBackForward: (options: HybridRequestOptions) => void
}
```

Calling `useBackForward` creates a scope in which callbacks are registered. It returns `onBackForward` and `reloadOnBackForward`, which add a callback to the scope when called.

## Examples

The following example reloads the page when a back or forward browser navigation is made, in order to refresh potentially-stale data.

```vue
<script setup lang="ts">
useBackForward({ // [!code focus:5]
	reload: {
		only: ['users'],
	},
})

defineProps<{
	users: Paginator<App.Data.UserData>
}>()
</script>
```

The following example calls the defined callback when a back or forward browser navigation is made.

```vue
<script setup lang="ts">
const { onBackForward } = useBackForward() // [!code focus]

onBackForward(({ url }) => { // [!code focus:3]
	console.log(`Back-forward navigation made to ${url}`)
})
</script>
```
