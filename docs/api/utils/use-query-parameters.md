# `useQueryParameters`

This composable returns a reactive object containing the current query parameters.

:::info Experimental
This function has not been dogfed yet and is considered experimental. Its API may change at any time. Feel free to give feedback on our Discord server.
:::

## Usage

This function may be useful to avoid having to pass query parameters from the controller to the front-end from.

Simply call `useQueryParameters` and access query parameters on the returned object:

```ts
// ?foo=bar
const parameters = useQueryParameters()
console.log(parameters.foo) // bar
```
