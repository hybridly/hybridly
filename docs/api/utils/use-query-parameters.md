# `useQueryParameters`

This function returns a reactive object containing the current query parameters.

| Related | [`useQueryParameter`](./use-query-parameter.md) |
| ------- | ----------------------------------------------- |

## Usage

This function is specifically useful to avoid passing query parameters from the controller to the page component as properties.

Simply call `useQueryParameters` and access query parameters on the returned object:

```ts
// ?foo=bar
const parameters = useQueryParameters()
console.log(parameters.foo) // bar
```

You may also specify a generic to specify the type of `parameters`:

```ts
const parameters = useQueryParameters<{ foo: string }>()
```
