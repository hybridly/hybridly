# Router response

Router utils that perform a hybrid request return a promise with an object described below. Note that navigations are asynchronous and awaitable.

```ts
interface NavigationResponse {
	response?: HttpResponse
	error?: Error
}
```

## `response`

- Type: `HttpResponse`

Contains the object returned by Hybridly's configured HTTP client.

## `error`

- Type: `Error`

Contains the error, if there was one. Hybridly exports `isHybridlyError`, `isNavigationCancelledError`, `isHttpAbortError` and `isHttpError` type guards to help you determine the type of error.
