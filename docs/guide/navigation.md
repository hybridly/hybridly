# Navigation

## Overview

Because Hybridly creates a single-page application, a special navigation needs to be made to avoid reloading the whole framework to load a page.

This can be done using a link component in the templates, or programmatically by using the [routing API](../api/router/utils.md).

## Using the component

`RouterLink` is a simple component that acts as an anchor tag, except it catches navigations to make hybrid requests.

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

It's often necessary to make navigations programmatically. This can be done using the [`router` API](../api/router/utils).

```ts
router.get(url, options)
router.post(url, options)
router.delete(url, options)
router.external(url, options)
router.reload(options)
router.navigate(options)
```

Learn more about the functions and options available in their [API documentation](../api/router/utils).

## Asynchronous requests

Hybridly supports asynchronous requests with `mode: 'async'`.

Async requests are useful for background refreshes where you don't want to create extra history entries or show a progress bar.

```ts
router.get({
	only: ['users'],
	mode: 'async',
})
```

:::tip Partial reloads
`router.reload()` already defaults to async mode, with `replace: true`, `preserveState: true`, and `preserveScroll: true`.
:::

## Cancelling and interrupting async requests

When multiple async requests can overlap, you can control interruption behavior using request options:

```ts
router.reload({
	only: ['notifications'],
	mode: 'async',
	group: 'navbar',
	interruptAsyncOnStart: 'same-group',
	cancelOnNavigation: true,
})
```

- `group` lets you scope async requests.
- `interruptAsyncOnStart` can interrupt `none`, `same-group`, or `all` requests.
- `cancelOnNavigation` interrupts the async request when a full navigation starts.
