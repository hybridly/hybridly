# Navigation

## Overview

Because Hybridly creates a single-page application, a special navigation needs to be made to avoid reloading the whole framework to load a page.

This can be done using a link component in the templates, or programmatically by using the [routing API](../api/router/utils.md).

## Using the component

`RouterLink` is a simple component that acts as an anchor tag, except it catches navigations to make hybrid requests.

```vue
<template>
  <div>
    <router-link href="/"> // [!code focus:3]
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
router.preload(url, options)
router.reload(options)
router.navigate(options)
```

Learn more about the functions and options available in their [API documentation](../api/router/utils).

## Preloading requests

Preloading pages will perform the usual underlying AJAX request, but instead of following-up with the navigation, Hybridly will cache the request result until the navigation is actually required.

This results in a smoother, snappy user experience that may make your application more enjoyable to use.

### Using the link component

`<router-link>` supports the [`preload` attribute](../api/components/router-link.md#preload), which will preload the corresponding URL when the link component is hovered or mounted, depending on its value.

```vue-html
<!-- Preloads when the link is hovered -->
<router-link href="/" preload>Home</router-link>

<!-- Preloads when the link mounts (on page load) -->
<router-link href="/" preload="mount">Home</router-link>
```

### Programmatically

Alternatively, you may use the [`router.preload(url, options)`](../api/router/utils.md#preload) function. As for the link component, the navigation will be cached until the next navigation, programmatic or not.
