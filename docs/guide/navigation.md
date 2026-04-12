---
outline: 2
---

# Navigation

<p class="preface">
Learn how to navigate between pages on the client side, how to use different HTTP methods, and how to update data transparently.
</p>

## Overview

Because Hybridly creates a single-page application, a special navigation needs to be made to avoid reloading the whole framework to load a page.

This is done using the [`<RouterLink>`](../api/components/router-link.md) component, or programmatically by using the [routing API](../api/router/navigation.md).

## Using the component

[`<RouterLink>`](../api/components/router-link.md) is a simple component that acts as an anchor tag, except it catches navigations to transform them into hybrid requests.

```vue
<script setup lang="ts">
import { RouterLink } from 'hybridly/vue'
</script>

<template>
	<router-link href="/">Home</router-link>
</template>
```

Learn more about the options available on its [API documentation](../api/components/router-link).

## Programmatically

In many cases, such as when submitting forms, handling refreshes from WebSocket, or for any other reason, you will need to trigger navigations programmatically.

This is done using the [`router` navigation API](../api/router/navigation).

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

Learn more about the functions and options available in their [API documentation](../api/router/navigation).

## Asynchronous requests

There can only be one navigation active at a time. When attempting a navigation while another is still pending, the previous one will be cancelled.

However, it is possible to make an asynchronous request that will not be prevented by a subsequent navigation, which is particularly useful to load or refresh data transparently.

You may read more about this on the [partial reloads](./partial-reloads.md) documentation.
