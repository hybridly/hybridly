---
outline: 'deep'
---

# Architecture configuration

## Overview

By default, Hybridly uses the following structure:

```
resources/
├── application/
│   ├── main.ts
│   └── root.blade.php
├── layouts/
│   └── default.vue
├── pages/
│   ├── index.vue
│   └── security/
│       └── login.vue
├── utils/
└── composables/
```

These conventions are good, but you may update or rename directories by creating a `hybridly.config.ts` file and returning a configuration object. 

This configuration will be used by the Vite plugin, the Vue adapter and the IDE extensions to provide consistent support throughout the tooling.

```ts
import { defineConfig } from 'hybridly/config'

export default defineConfig({
	domains: true,
})
```

## Options

### `eager`

- **Type**: `boolean`
- **Default**: `true`

Defines whether page components are eagerly or lazily-loaded. By default, they are eagerly-loaded, but as an application grows, it may be necessary to change this setting.

### `root`

- **Type**: `string`
- **Default**: `resources`

Defines the name of the root directory, relative to the base directory.

### `pages`

- **Type**: `string`
- **Default**: `pages`

Defines the name of the pages directory, relative to the root or domain directory.

### `layouts`

- **Type**: `string`
- **Default**: `layouts`

Defines the name of the layouts directory, relative to the root or domain directory.

### `domains`

- **Type**: `boolean` or `string`
- **Default**: `false`

If `true`, enables domain-driven support, with which Hybridly expects the following architecture:

```
<root>/
└── <domains>/
    ├── security/
    │   ├── components/
    │   └── <pages>/
    │       ├── login.vue
    │       ├── forgot-password.vue
    │       └── ...
    └── domain1/
        ├── <pages>/
        ├── <layouts>/
        ├── components/
        └── utils/
```

Directory name between brackets are defined by their corresponding option (eg. `<root>` is `resources` by default).
