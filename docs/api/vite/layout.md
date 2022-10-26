# Layout configuration

## Overview

The Vite plugin provides a way to define the current persistent layout through an attribute of the `<template>` tag of a single-file component.

```vue
<template layout="profile">
  <!-- ... -->
</template>
```

Omitting the name will use the default layout.

```vue
<template layout>
  <!-- ... -->
</template>
```

Additionally, it's possible to use nested persistent layouts by separating layout names with a comma.

```vue
<template layout="profile, main">
  <!-- ... -->
</template>
```

## Configuration

This plugin can be configured through the `layout` option of the `hybridly` plugin in `vite.config.ts`.

Setting `layout` to `false` disables the feature.

### `defaultLayoutName`

- Type: `string`
- Default: `default`

Defines the name of the layout that will be used when no layout name is provided.

### `templateRegExp`

- Type: `RegExp`

Defines the regular expression that parses the `<template>` tag for finding the defined layout.

### `directory`

- Type: `string`
- Default: `./resources/views/layouts`

Defines the directory in which layouts are stored.

### `resolve`

- Type: `(name: string) => string`

Overrides how the path of the given layout name is generated.
