# `route`

This helper function generates a URL based on a route name and the provided arguments.

| Related | [Routing](../../guide/routing.md), [`useRoute`](use-route.md) |
| ------- | ---------------------------------------------------------------------------- |

## Usage

```ts
function route<T extends RouteName>(
  name: T,
  parameters?: RouteParameters<T>,
  absolute?: boolean
): string
```

`route` requires at least a route name. It accepts the route's parameters as the second argument, and a boolean that determines if the URL should be absolute, which is the default, as the third.

The route name and parameters have TypeScript support through the Vite plugin. To learn how to set it up, read the [routing documentation](../../guide/routing.md#generating-urls).

:::info Notes
- If the route has mandatory parameters and they are not provided, the `route` function will throw an error.
- This function returns a non-reactive string. To make it reactive, wrap it in a computed property or use a watcher.
:::

## Example

The example below shows how to generate a URL based on a defined route.

```php
// routes/web.php
Route::get('/users/{user}', [UsersController::class, 'show'])
  ->name('users.show')
```

```ts
<script setup lang="ts">
const $props = defineProps<{ // [!code focus:3]
  user: App.Data.UserData
}>()

const index = route('users.show', { user: $props.user }) // [!code focus]
</script>
```
