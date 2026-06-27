# Router options

Most `router` functions accept an _options_ argument for configuring the request. The purpose of each of these options is documented here.

## `url`

- Type: `UrlResolvable`

The URL to navigate to. Can be a `string`, an [`URL`](https://developer.mozilla.org/en-US/docs/Web/API/URL) object or a [`Location`](https://developer.mozilla.org/en-US/docs/Web/API/Location) object.

## `method`

- Type: `GET`, `POST`, `PUT`, `PATCH` or `DELETE` (uppercase or lowercase)

HTTP method that will be used for the request. When [uploading files](../../guide/file-uploads.md#limitations), do not use `POST` but instead add a `_method: 'POST'` property to the body of the request.

## `mode`

- Type: `'navigation' | 'async'`

Defines how the request should behave:

- `navigation` performs a full navigation.
- `async` performs a background data refresh and, by default, does not show progress and replaces the current history entry.

Most `router.reload()` calls use `async` mode by default.

## `data`

- Type: `RequestData`

Body of the request. Can be or contain a `FormData` object.

## `updateImmediately`

- Type: `(properties: Readonly<T>) => Partial<T> | undefined`

Defines a pure optimistic property transform that runs before the request is sent.

Return only the top-level properties that should be replaced. The callback receives the currently rendered view properties and may run more than once while pending requests settle, so it should not mutate its argument or rely on side effects.

```ts
const liked = !character.liked

router.post(route('characters.like'), {
	data: {
		id: character.id,
		liked,
	},
	updateImmediately: (properties) => ({
		characters: properties.characters.map((current) =>
			current.id === character.id
				? { ...current, liked }
				: current
		),
	}),
})
```

Read the documentation on [optimistic responses](../../guide/optimistic-responses.md) for more information.

## `group`

- Type: `string`

Optional group identifier for async requests. It is used together with `interruptAsyncOnStart` to define which pending async requests should be interrupted when a new one starts.

## `cancelOnNavigation`

- Type: `boolean`

When set to `true`, this async request is interrupted when a new navigation request starts.

## `interruptAsyncOnStart`

- Type: `'none' | 'all' | 'same-group'`

Defines which async requests are interrupted when this request starts:

- `none`: interrupt nothing.
- `same-group`: interrupt requests in the same `group`.
- `all`: interrupt all pending async requests.

## `only`

- Type: `string` or `string[]`

Defines the properties that will be included in the response. All other properties except the [persistent ones](../../guide/partial-reloads.md#persistent-properties) will be excluded.

Read the documentation on [partial reloads](../../guide/partial-reloads.md) for more information.

## `except`

- Type: `string` or `string[]`

Defines the properties that will be excluded from the response. Specified [persistent properties](../../guide/partial-reloads.md#persistent-properties) will also be excluded.

Read the documentation on [partial reloads](../../guide/partial-reloads.md) for more information.

## `reset`

- Type: `string` or `string[]`

Defines properties that should be cleared before applying incoming data.

This is useful when reloading [mergeable properties](../../guide/partial-reloads.md#mergeable-properties) that should be reset instead of merged.

## `preserveState`

- Type: `boolean | ((options: NavigationOptions) => boolean)`

Defines whether the current view component state should be preserved for this navigation.

## `preserveUrl`

- Type: `boolean | ((options: NavigationOptions) => boolean)`

Defines whether the current URL should be preserved. This is an advanced option that should not be used often.

## `preserveScroll`

- Type: `boolean | ((options: NavigationOptions) => boolean)`

Defines whether to preserve the position of the document element's and the scroll regions' scrollbars.

Read the documentation on [scroll management](../../guide/scroll-management.md) for more information.

## `replace`

- Type: `boolean | ((options: NavigationOptions) => boolean)`

Defines whether to replace the current history state instead of adding an entry. This affects the browser's "back" and "forward" behavior.

## `viewTransition`

- Type: `boolean | string | string[]`

Defines whether to use a [View Transition](https://developer.mozilla.org/en-US/docs/Web/API/View_Transition_API/Using_types) for the navigation.

When a `string` or `string[]` is given, these values are used as transition types.

## `hooks`

- Type: `Partial<RequestHooks>`

Defines hooks for the [lifecycle of the request](../../guide/hooks.md#request-lifecycle-events). Read the documentation on [hooks](../../guide/hooks.md) for more information.

## `headers`

- Type: `Record<string, string>`

Defines additional headers for the request.

## `errorBag`

- Type: `string`

Defines the bag in which validation errors will be put.

## `useFormData`

- Type: `boolean`

When set to `true`, forces the conversion of the `data` option to a `FormData` object.

## `spoof`

- Type: `boolean`

Automatically [spoofs](https://laravel.com/docs/9.x/routing#form-method-spoofing) the method when submitting a `FormData` with the `PUT`, `PATCH` or `DELETE` method.

## `abortController`

- Type: `AbortController`

Abort controller used for this request. This can be used to manually cancel a specific request instance.

## `transformUrl`

- Type: `UrlTransformable`

Object which properties will affect the provided `url`. Can also be a callback receiving the current `URL` and returning a `UrlTransformable` object.

## `progress`

- Type: `boolean`

When set to `false`, the request will not have a progress bar. Async requests default to `false` unless explicitly enabled.
