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
	pages: import.meta.glob('@/views/pages/**/*.vue', { eager: true }),
  enhanceVue: (vue) => {
    vue.use(createHead({ // [!code hl:3]
			titleTemplate: (title) => title ? `${title} - Blue Bird` : 'Blue Bird',
		})) 
  }
})
```

`createHead` may be used to define a default `titleTemplate` callback that will be active for the entire application.

## Usage

The latest `useHead` call is persisted, which means you may override `titleTemplate` on a layout. 

Page titles may be defined using the `title` property in page components.

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
  <!-- Page component -->
</template>
```
:::

For more information regarding the functionalities of `@vueuse/head`, refer to [its documentation](https://github.com/vueuse/head).
