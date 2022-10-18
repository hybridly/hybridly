# Preserving URLs

## Overview

Hybrid responses return an `url` property. This is what allows hybrid application to use the routing definitions from the back-end: after a hybrid request, the current URL is updated to the one received in the response.

## Infinite scrolling

In almost all situations, this makes perfect sense. However, when consuming pagination through infinite scrolling, it is not desired to update the URL with the `?page` query parameters, because — aside from being aesthetically unpleasant — reloading the page using the browser would skip the previous paginations, only showing the content for the current pagination index.

For this reason, it's possible to preserve the URL of the current page after a request instead of updating it.

```ts
const url = $props.chirps.meta.next_page_url!

router.get(url, {  // [!vp focus]
	preserveState: true,
	preserveScroll: true,
	preserveUrl: true, // [!vp focus]
	only: ['chirps'],
	hooks: {
		success: () => chirps.value.push(...$props.chirps.data),
	},
}) // [!vp focus]
```
