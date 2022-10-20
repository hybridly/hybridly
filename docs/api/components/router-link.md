# `<RouterLink>`

This built-in component can be used to replace [anchor tags](https://developer.mozilla.org/en-US/docs/Web/HTML/Element/a) to navigate from a hybrid page to another.

This component is a wrapper around Vue's [`<Component>` ](https://vuejs.org/api/built-in-special-elements.html#component). By default, it creates anchors elements but intercept their click handlers to make [hybrid navigations](../../guide/navigation.md).

## `href`

- **Required**: true
- **Type**: `string`

Similar to the `<a>` tag, accepts the hyperlink to navigate to. If this doesn't point to a hybrid page, set the [`external`](#external) property to `true`.

### Usage

```vue
<template>
  <router-link :href="route('index')"> // [!vp focus]
    Home
  </router-link>
</template>
```

## `external`

- **Type**: boolean

When set to `true`, disables the custom click handler. This must be used when navigating to external websites or non-hybrid pages — otherwise, the a hybrid request will be made and will result into an error.

### Usage

```vue
<template>
  <router-link
    v-for="link in navigation"
    :key="link.url"
    :href="link.url"
    :external="link.external" // [!vp focus]
    v-text="link.label"
  />
</template>
```

## `as`

- **Type**: `string` or `Component`

Defines the tag or component to render as.

### Usage

```vue
<script setup lang="ts">
import BaseButton from '@/views/components/base-button.vue' // [!vp focus]
</script>

<template>
  <router-link
    :as="BaseButton" // [!vp focus]
    method="POST"
    :href="route('chirps.delete')"
  >
    Delete
  </router-link>
</template>
```

## `method`

- **Type**: `GET`, `POST`, `PUT`, `PATCH` or `DELETE`

Defines the method that will be used when making the hybrid request. May be lowercase or uppercase.

## `data`

- **Type**: object

Optional data to be sent with the hybrid request. This is the same as using `data` in a [programmatic navigation](../utils/router.md).

## `options`

- **Type**: `VisitOptions`

Optional options for the hybrid request. This is the same as the `options` argument in [programmatic navigations](../utils/router.md).

## `disabled`

- **Type**: boolean

When set to `true`, the click handler will not be triggered and the `disabled` HTML attribute will be added.
