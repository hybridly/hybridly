# `registerHook`

This helper function may be used to add a listener on a [hook](../../guide/hooks.md).

When called inside a Vue component, the listener will unregister itself automatically when the component is unmounted.

| Related | [Plugins](../../guide/plugins.md), [Hooks](../../guide/hooks.md) |
| ------- | ---------------------------------------------------------------- |

## Usage

`registerHook` takes two mandatory arguments: the [name of the hook](../../guide/hooks.md#available-hooks), and a callback that takes as a parameter the data from this hook. 

It returns another function that will unregister the hook.

When the `once` option is set to `true`, the hook will only be executed once.

```ts
registerHook('navigated', ({ payload }) => {
  console.log(`Navigated to ${payload.url}`)
}, { once: true })
```
