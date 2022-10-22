
# Router response

Router utils that perform a hybrid request return a promise with an object described below. Note that navigations are asynchronous and awaitable.

```ts
interface NavigationResponse {
	response?: AxiosResponse
	error?: {
		type: string
		actual: Error
	}
}
```

## `response`

- Type: `AxiosResponse`

Contains the object returned by Axios.

## `error`

- Type: `{ type: string, actual: Error }`

Contains the details of the navigation errors, if any.

### `type`

The error type is the name of the constructor of the actual error instance:
- `NavigationCancelledError`
- `AbortError`
- `NotAHybridResponseError`

### `actual`

The actual error instance.
