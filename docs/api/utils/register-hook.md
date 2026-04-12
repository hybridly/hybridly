# `registerHook`

<p class="preface">
This helper registers a listener on a hook and automatically unregisters it when used inside a Vue component that gets unmounted.
</p>

## Usage

`registerHook` takes two mandatory arguments: the [name of the hook](../../guide/hooks.md#available-hooks), and a callback that takes as a parameter the data from this hook.

It returns another function that will unregister the hook.

When the `once` option is set to `true`, the hook will only be executed once.

```ts
registerHook('navigated', ({ payload }) => {
	console.log(`Navigated to ${payload.url}`)
}, { once: true })
```
