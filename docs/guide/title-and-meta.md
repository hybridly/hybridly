# Title & meta

## Overview

Hybridly doesn't ship with any tool to manage the `title` or other `meta` tags of your pages. Instead, we recommend using [`@vueuse/head`](https://github.com/vueuse/head). It is simple of use and supports SSR.

## Installation

Add `@vueuse/head` to your dependencies:

```bash
npm i -D @vueuse/head
```

In `main.ts`, import `createHead` and register its return value as a plugin.

```ts
import { createApp } from 'vue'
import { initializeHybridly } from 'hybridly/vue'
import { createHead } from '@vueuse/head' // [!code hl]
import 'virtual:hybridly/router'

initializeHybridly({
	cleanup: !import.meta.env.DEV,
	pages: import.meta.glob('@/views/pages/**/*.vue', { eager: true }),
  enhanceVue: (vue) => {
    vue.use(createHead())  // [!code hl]
  }
})
```

## Usage

A title template could be defined in a persistent (or normal) layout. That way, titles can be set in page components.

```vue
<script setup lang="ts">
import { useHead } from '@vueuse/head'

useHead({
	titleTemplate: (title) => `${title} - Blue Bird`,
})
</script>

<template>
  <!-- Some layout here -->
  <slot />
</template>
```

```vue
<script setup lang="ts">
import { useHead } from '@vueuse/head'

useHead({
	title: 'Recent chirps',
})
</script>

<template layout="default">
  <!-- Page component -->
</template>
```

For more information regarding the functionalities of `@vueuse/head`, refer to [its documentation](https://github.com/vueuse/head).
