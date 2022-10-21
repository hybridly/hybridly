# `registerHook`

This helper function may be used to add a listener on a [hook](../../guide/hooks.md).

## Usage

```ts
function registerHook<T extends keyof Hooks>(
  hook: T,
  fn: Hooks[T],
  options?: {
    once?: boolean
  }
): () => void
```

`registerHook` takes two arguments: the [name of the hook](../../guide/hooks.md#available-hooks), and a callback that takes as a parameter the data from this hook. It returns another function that will unregister the hook.

When the `once` option is set to `true`, the hook will only be executed once.

## Example

The following example will log the URL after each navigation.

```ts
registerHook('navigated', ({ payload }) => {
  console.log(`Navigated to ${payload.url}`)
})
```
