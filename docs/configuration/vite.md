# Vite configuration

## Overview

The Vite plugin, in addition to providing Hybridly-specific features, wraps a few external Vite plugins that are often needed in single-page applications. 

These plugins are provided with a good default configuration, but are still individually configurable.

## Laravel

`laravel-vite-plugin`, the official Vite plugin for Laravel, is included and its `input` option is configured to `<root>/application/main.ts`.

To customize this, use the `laravel` option:

```ts
import { defineConfig } from 'vite'
import hybridly from 'hybridly/vite'

export default defineConfig({
	plugins: [
		hybridly({ // [!code focus:5]
			laravel: {
				valetTls: true,
			},
		}),
	],
})
```

The plugin can be disabled by setting `laravel` to `false`.

## Vue

`@vitejs/plugin-vue`, the official Vue 3 plugin, is included by default. It can be disabled or configured by setting the `vue` option:

```ts
import { defineConfig } from 'vite'
import hybridly from 'hybridly/vite'

export default defineConfig({
	plugins: [
		hybridly({ // [!code focus:5]
			vue: {
				reactivityTransform: true
			},
		}),
	],
})
```

## Run

`vite-plugin-run`, which allows for executing shell commands when files are updated in the project, is used to generate TypeScript definitions and language files automatically.

You may add custom runner rules by using the `run` option:

```ts
import { defineConfig } from 'vite'
import hybridly from 'hybridly/vite'

export default defineConfig({
	plugins: [
		hybridly({ // [!code focus:5]
			run: [
				{ /* Custom runner object */ },
			],
		}),
	],
})
```

The plugin can be disabled by setting `run` to `false`.

## Auto-imports

`unplugin-auto-imports` is included to support auto-importing of Hybridly utils, Vue functions such as `ref` or `computed`, or utils and composables in your `<root>/composables` and `<root>/utils` directories.

You may disable it by setting `autoImports` to `false` or override its configuration by using the same key:

```ts
import { defineConfig } from 'vite'
import hybridly from 'hybridly/vite'

export default defineConfig({
	plugins: [
		hybridly({ // [!code focus:7]
			autoImports: {
				eslintrc: {
					enabled: true
				}
			},
		}),
	],
})
```

## Vue components

`unplugin-vue-components` is included to support auto-importing of Vue components.

You may disable it by setting `vueComponents` to `false`, or you may update its configuration instead:

```ts
import { defineConfig } from 'vite'
import hybridly from 'hybridly/vite'
import { HeadlessUiResolver } from 'unplugin-vue-components/resolvers' // [!code focus]

export default defineConfig({
	plugins: [
		hybridly({ // [!code focus:7]
			vueComponents: {
				resolvers: [
					HeadlessUiResolver(),
				],
			},
		}),
	],
})
```

Note that disabling it would also de-active auto-importing of [icons](#icons) and built-in components such as [`<router-link>`](../api/components/router-link.md).

## Icons

`unplugin-icons` is iuncluded to provide Iconify and custom icons support.

It may be disabled by setting `icons` to `false`, or you may adjust its configuration if needed:

```ts
import { defineConfig } from 'vite'
import hybridly from 'hybridly/vite'

export default defineConfig({
	plugins: [
		hybridly({ // [!code focus:5]
			icons: {
				autoInstall: false
			},
		}),
	],
})
```

Icons can be imported manually or automatically:

```vue
<script setup lang="ts">
import IconAccessibility from '~icons/carbon/accessibility' // [!code focus]
</script>

<template>
	<i-carbon-accessibility class="w-4" /> // [!code focus]
</template>
```


## Custom icons

Custom icon collections may be defined using the `customIcons` option:

```ts
import { defineConfig } from 'vite'
import hybridly from 'hybridly/vite'

export default defineConfig({
	plugins: [
		hybridly({ // [!code focus:3]
			customIcons: ['aircraft'],
		}),
	],
})
```

To register icons, add `.svg` files under the `<root>/icons/<collection>` directories:

```
resources/
└── icons/
    └── aircraft/
        ├── pc12.svg
        ├── pc24.svg
        └── sf50.svg
```

Custom icons can be then be imported like [other icons](#icons):

```vue
<script setup lang="ts">
import pc12 from '~icons/aircraft/pc12'  // [!code focus]
</script>

<template>
	<i-aircraft-pc12 class="w-10" /> // [!code focus]
</template>
```
