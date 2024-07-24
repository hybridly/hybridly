---
outline: 'deep'
---

# `useQueryParameter`

This composable returns a `Ref` that contains the specified query parameter.

| Related | [`useQueryParameters`](./use-query-parameters.md) |
| ------- | ------------------------------------------------- |

## Usage

You may pass a query parameter name as the first parameter of the function. The return value will be a `Ref` containing the value of that query parameter.

This function is specifically useful to avoid passing query parameters from the controller to the page component as properties.

```ts
// ?foo=bar
const foo = useQueryParameter('foo')
console.log(foo.value) // bar
```

## Options

The second parameter of the function accepts an object with the following options:

```ts
interface Options<T extends RouteParameter, R> {
	defaultValue?: MaybeRefOrGetter<R>
	transform?: 'number' | 'bool' | 'string' | 'date' | TransformFn<T, R>
}
```

### `defaultValue`

You may use the `defaultValue` option to specify a value when there is no query parameter:

```ts
const count = useQueryParameter('count', { defaultValue: 0 })
console.log(count.value) // 0
```

The type of the ref will be inferred by the type of the specified `defaultValue`.

### `transform`

The `transform` option may be used to transform the query parameter using a custom or predefined function.

#### Number

Setting `transform` to `number` will convert the query parameter to a number. Note that if the query parameter is not a valid number, this will result in `NaN`.

You may specify a `defaultValue` so a missing query parameter does not evaluate to `NaN`.

```ts
// ?count=1
const count = useQueryParameter('count', { transform: 'number' })
console.log(count.value) // 1

// no query parameter
const count = useQueryParameter('count', {
	transform: 'number',
	defaultValue: 0,
})

console.log(count.value) // 0
```

#### Boolean

Setting `transform` to `bool` will infer the query parameter as a boolean.

```ts
// ?success=1
const success = useQueryParameter('success', { transform: 'bool' })
console.log(success.value) // true
```

#### String

Setting `transform` to `string` will ensure the query parameter is a string.

```ts
// ?foo=1
const foo = useQueryParameter('foo', { transform: 'string' })
console.log(foo.value) // '1'
```

#### Date

Setting `transform` to `date` will convert the query parameter to a date instance. However, no check is performed to ensure the string is a valid date string.

```ts
// ?from=2024-01-01
const from = useQueryParameter('from', { transform: 'date' })
console.log(from.value) // Mon Jan 01 2024 01:00:00 GMT+0100
```

#### Custom function

If you have specific logic, you may pass a function as the `transform` option.

```ts
// ?count=1
const count = useQueryParameter('count', {
	transform: (value) => value + 1,
	defaultValue: 0
})

console.log(count.value) // 2
```
