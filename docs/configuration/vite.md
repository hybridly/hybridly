# Vite configuration

## Overview

The Vite plugin, in addition to providing Hybridly-specific features, wraps a few external Vite plugins that are often needed in single-page applications.

These plugins are provided with a good default configuration, but are still individually configurable.

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

`unplugin-auto-import` is included to support auto-importing of Hybridly utils, Vue functions such as `ref` or `computed`, or utils and composables in your `<root>/composables` and `<root>/utils` directories.

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

Currently, Hybridly auto-imports the following:
- Headless UI and Radix components, if the corresponding packages are installed
- All components specified by your [current architecture](../guide/architecture.md)
- Components in `resources/components`
- The [Link](../api/components/router-link.md) component

You may disable it by setting `vueComponents` to `false`, or you may update its configuration instead:

```ts
import { defineConfig } from 'vite'
import hybridly from 'hybridly/vite'

export default defineConfig({
	plugins: [
		hybridly({ // [!code focus:7]
			vueComponents: {
				linkName: 'HybridlyLink',
			},
		}),
	],
})
```

:::info Disabling
Disabling `vueComponents` would also de-active auto-importing of [icons](#icons) and built-in components such as [`<router-link>`](../api/components/router-link.md).
:::

## Icons

`unplugin-icons` is included to provide Iconify and custom icons support.

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

## Warn on local builds

When working on Vite-powered applications using Laravel, the development server generally has to be started, but some people prefer not running it until actually needed.

This may create confusion when forgetting about it and updating front-end-related code, because changes will not appear in the browser until the server is started.

To help with this, this plugin displays a discreet warning at the bottom of the screen when the application has been built locally (eg. when `APP_ENV` is `local`).

You may disable this warning by setting `warnOnLocalBuilds` to `false`.

```ts
import { defineConfig } from 'vite'
import hybridly from 'hybridly/vite'

export default defineConfig({
	plugins: [
		hybridly({  // [!code focus:3]
			warnOnLocalBuilds: false,
		}),
	],
})
```

## Setting the php executable path

To set the path to the php executable the `PHP_EXECUTABLE_PATH` environment variable to your custom path.

You can set the environment variable by placing `PHP_EXECUTABLE_PATH=custom/path/to/php` before the `vite` command in the dev and build script.

```json
{
	"private": true,
	"scripts": {
		"dev": "PHP_EXECUTABLE_PATH=custom/path/to/php vite", // [!code focus]
		"build": "PHP_EXECUTABLE_PATH=custom/path/to/php vite build" // [!code focus]
	},
	"devDependencies": {
		"axios": "^1.3.0",
		"hybridly": "0.5.7",
		"lodash": "^4.17.19",
		"postcss": "^8.1.14",
		"vite": "^5.0.8",
		"vue": "^3.2.41"
	}
}
```
