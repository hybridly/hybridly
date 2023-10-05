# Title & meta

## Overview

Hybridly doesn't ship with any tool to manage the `title` or other `meta` tags of your views. Instead, we recommend using [`@unhead/vue`](https://unhead.unjs.io/). It is simple of use and supports SSR.

## Installation

Add `@unhead/vue` to your dependencies:

```bash
npm i -D @unhead/vue
```

In `main.ts`, import `createHead` and register its return value as a plugin.

```ts
import { createApp } from 'vue'
import { initializeHybridly } from 'virtual:hybridly/config'
import { createHead } from '@unhead/vue' // [!code hl]

initializeHybridly({
  enhanceVue: (vue) => {
    const head = createHead()

    head.push({titleTemplate: (title) => title ? `${title} - Blue Bird` : 'Blue Bird'})

    vue.use(head)
  }
})
```

`createHead` may be used to define a default `titleTemplate` callback that will be active for the entire application.

## Usage

The latest `useHead` call is persisted, which means you may override `titleTemplate` on a layout.

Page titles may be defined using the `title` property in view components.

:::code-group
```vue [layouts/default.vue]
<script setup lang="ts">
useHead({
	titleTemplate: (title) => `${title} - Blue Bird`, // [!code hl]
})
</script>

<template>
  <!-- Some layout here -->
  <slot />
</template>
```

```vue [chirps/index.vue]
<script setup lang="ts">
useHead({
	title: 'Recent chirps', // Recent chirps - Blue Bird // [!code hl]
})
</script>

<template layout="default">
  <!-- view component -->
</template>
```
:::

For more information regarding the functionalities of `@unhead/vue`, refer to [its documentation](https://unhead.unjs.io/usage/composables/use-head).
