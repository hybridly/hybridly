---
outline: "deep"
---

# Navigation

<p class="preface">
Learn how to navigate between pages on the client side, how to use different HTTP methods, and how to update data transparently.
</p>

## Overview

Because Hybridly creates a single-page application, a special navigation needs to be made to avoid reloading the whole framework to load a page.

This is done using the [`<RouterLink>`](../api/components/router-link.md) component, or programmatically by using the [routing API](../api/router/utils.md).

## Using the component

[`<RouterLink>`](../api/components/router-link.md) is a simple component that acts as an anchor tag, except it catches navigations to transform them into hybrid requests.

```vue
<template>
	<div>
		<router-link href="/">
			// [!code focus:3] Home
		</router-link>
	</div>
</template>
```

Learn more about the options available on its [API documentation](../api/components/router-link).

## Programmatically

In many cases, such as when submitting forms, handling refreshes from WebSocket, or for any other reason, you will need to trigger navigations programmatically.

This is done using the [`router` API](../api/router/utils).

```ts
router.to(route, parameters, options)
router.reload(options)
router.get(url, options)
router.post(url, options)
router.put(url, options)
router.patch(url, options)
router.delete(url, options)
router.external(url, options)
router.navigate(options)
```

Learn more about the functions and options available in their [API documentation](../api/router/utils).

## Asynchronous requests

By default, there can only be one navigation. When attempting a navigation while another is still pending, the previous one will be cancelled.

However, it is possible to make an asynchronous request that will not be prevented by a subsequent navigation. This is useful for reloading or fetching data, which is typically done using [partial reloads](./partial-reloads.md).

```ts
router.get({
	only: ['users'],
	mode: 'async', // [!code hl]
})
```

Note that partial reloads (hybrid requests using `only` or `except`) already use async mode by default. In most cases, you won't need to specify the `mode`.

### Cancelling and interrupting async requests

When multiple asynchronous requests can overlap, you may control their interruption behavior.

```ts
router.reload({
	only: ['notifications'],
	mode: 'async',
	group: 'navbar',
	interruptAsyncOnStart: 'same-group',
	cancelOnNavigation: true,
})
```

#### `group`

This option allows for grouping asynchronous request together. This setting interracts with other requests in the same group that use `interruptAsyncOnStart`.

#### `interruptAsyncOnStart`

This option configures whether that request should interrupt other requests.

- `none` (default) — does not interrupt any request.
- `same-group` — interrupts requests that share the same `group`.
- `all` — interrupts all requests.

#### `cancelOnNavigation`

This option configures whether the request should be cancelled when a full navigation starts. This is useful to prevent race conditions.
