# Router configuration

## Overview

The Vite plugin provides the functionality behind the built-in [`route`](../utils/route.md) util. It uses an artisan command to obtain the named routes and generate a definition file to provide typings for the `route` function.

For this functionality to work, a virtual import is required. This typically goes in the entry file.

```ts
import { initializeHybridly } from 'hybridly/vue'
import 'virtual:hybridly/router' // [!code focus]
import './tailwind.css'

initializeHybridly({
	pages: import.meta.glob('@/views/pages/**/*.vue', { eager: true }),
})
```

## Configuration

This plugin can be configured through the `route` option of the `hybridly` plugin in `vite.config.ts`.

Setting `route` to `false` disables the feature.

### `dts`

- **Type**: `string`
- **Default**: `./resources/types/routes.d.ts`

Defines the path to the TypeScript definition file that will be automatically generated. We recommend adding this path in `.gitignore`.

### `php`

- **Type**: `string`
- **Default**: `php`

Typically, PHP is available in the `PATH` environment variable, but if it's not, you may configure its path through this option.

### `watch`

- **Type**: `RegExp[]`
- **Default**: `[/routes\/.*\.php/]`

Defines the regular expression that determines if the TypeScript definitions should be re-generated when a file is saved in the project.

By default, the provided regular expression will trigger the code generation when a `.php` file in `routes/` is updated.
