# Router utils

The `router` object contains a few utils that can be used to programmatically navigate through the application.

Most of the navigation functions accept an `options` argument whose properties are documented in the [router options](./options.md) page.

When a `url` argument is accepted, its type is the same as the `url` property of the `options` argument.

## `get`, `post`, `put`, `patch`, `delete`

This function initiates a programmatic navigation to the given URL using the corresponding method. 

When performing non-`GET` requests, by default, the `preserveState` option will be set to `true`.

```ts
router.get(url, options)
router.post(url, options)
router.put(url, options)
router.patch(url, options)
router.delete(url, options)
```

## `reload`

This function initiates a programmatic navigation to the current URL. By default, the navigation will preserve the scroll position and the component's state.

```ts
router.reload(options)
```

## `local`

This function initiates a local-only navigation. This navigation will not reach the server — it will only re-render the specified (or current) component with the specified properties.

```ts
router.local(options)
```

Its `options` argument is different from the other navigation functions: only the `replace`, `preserveScroll` and `preserveState` options are available. Additionally, a `component` and a `properties` options are also available.

```ts
interface ComponentNavigationOptions {
	/** Name of the component to use. */
	component?: string
	/** Properties to apply to the component. */
	properties?: Properties
}
```

:::info Global properties
Note that, because of roundtrip-less nature, global properties will not be updated when using this method.
:::

## `external`

This function initiates an external, hard navigation. This is an equivalent of using `document.location.href`.

If provided, the `data` object will be converted to query parameters.

```ts
router.external(url, data)
```


## `navigate`

This function initiates a programmatic navigation without any specific default.

```ts
router.navigate(options)
```

## `abort`

This function aborts the current request. The `abort` hook will be triggered.

## `dialog.close`

This function closes the current dialog. It takes the same options as the other router functions. 

```ts
router.dialog.close()
```

## `history.get`

This function fetches the given key from the history state. The state is specific to the current [history entry](https://developer.mozilla.org/en-US/docs/Web/API/History).

```ts
router.history.get(key)
```

## `history.remember`

This function sets the given key and value in the history state. The state is specific to the current [history entry](https://developer.mozilla.org/en-US/docs/Web/API/History).

```ts
router.history.remember(key, value)
```
