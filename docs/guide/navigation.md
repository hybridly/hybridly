# Navigation

## Overview

Because Hybridly creates a single-page application, a special navigation needs to be made to avoid reloading the whole framework to load a page.

This can be done using a link component in the templates, or programmatically by using the routing API.

## Using the component

`RouterLink` is a simple component that acts as an anchor tag, except it catches navigation to make hybrid requests.

```vue
<script setup lang="ts">
import { RouterLink } from 'hybridly/vue' // [!vp focus]
</script>

<template>
  <div>
    <router-link href="/"> // [!vp focus:3]
      Home
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

Learn more about the methods and options available on their [API documentation](../api/router/utils).
