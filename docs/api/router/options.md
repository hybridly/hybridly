# Router options

Most `router` functions accept an _options_ argument for configuring the request. The purpose of each of these options is documented here.

## `url`

- Type: `UrlResolvable`

The URL to navigate to. Can be a `string`, an [`URL`](https://developer.mozilla.org/en-US/docs/Web/API/URL) object or a [`Location`](https://developer.mozilla.org/en-US/docs/Web/API/Location) object.

## `method`

- Type: `POST`, `GET`, `PUT`, `PATCH` or `DELETE`

HTTP method that will be used for the request. When [uploading files](../../guide/file-uploads.md#limitations), do not use `POST` but instead add a `_method: 'POST'` property to the body of the request.

## `data`

- Type: `RequestData`

Body of the request. Can be or contain a `FormData` object.

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

## `hooks`

- Type: `Record<HookName, Function>`

Defines hooks for the lifecycle of the request.

Read the documentation on [hooks](../../guide/hooks.md) for more information.

## `headers`

- Type: `Record<string, string>`

Defines additionnal headers for the request.

## `errorBag`

- Type: `string`

Defines the bag in which validation errors will be put.

## `useFormData`

- Type: `boolean`

When set to `true`, forces the conversion of the `data` option to a `FormData` object.

## `spoof`

- Type: `boolean`

Automatically [spoofs](https://laravel.com/docs/9.x/routing#form-method-spoofing) the method when submitting a `FormData` with the `PUT`, `PATCH` or `DELETE` method.

## `transformUrl`

- Type: `UrlTransformable`

Object which properties will affect the provided `url`. Can also be a callback that returns an `UrlTransformable`.
