# Router options

Most `router` functions accept an _options_ argument for configuring the request. The purpose of each of these options is documented here.

## `url`

- Type: `UrlResolvable | () => UrlResolvable`

The URL to navigate to. Can be a `string`, an [`URL`](https://developer.mozilla.org/en-US/docs/Web/API/URL) object or a [`Location`](https://developer.mozilla.org/en-US/docs/Web/API/Location) object.

## `method`

- Type: `POST`, `GET`, `PUT`, `PATCH` or `DELETE`

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

Defines the properties that will be included in the response. All other properties except the [persistent ones](../../guide/persistent-properties.md) will be excluded.

Read the documentation on [partial reloads](../../guide/partial-reloads.md) for more information.

## `except`

- Type: `string` or `string[]`

Defines the properties that will be excluded from the response. Specified [persistent properties](../../guide/persistent-properties.md) will also be excluded.

Read the documentation on [partial reloads](../../guide/partial-reloads.md) for more information.

## `preserveState`

- Type: `boolean`

Defines whether the component should be fully re-rendered, thus preserving its internal state.

## `preserveUrl`

- Type: `boolean`

Defines whether the current URL should be preserved. This is an advanced option that should not be used often.

## `preserveScroll`

- Type: `boolean`

Defines whether to preserve the position of the document element's and the scroll regions' scrollbars.

Read the documentation on [scroll management](../../guide/scroll-management.md) for more information.

## `replace`

- Type: `boolean`

Defines whether to replace the current history state instead of adding an entry. This affects the browser's "back" and "forward" behavior.

## **`hooks`**

- Type: `Record<HookName, Function>`

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

Object which properties will affect the provided `url`. Can also be a callback that returns an `UrlTransformable`.

## `progress`

- Type: `boolean`

When set to `false`, the request will not have a progress bar. Async requests default to `false` unless explicitly enabled.
