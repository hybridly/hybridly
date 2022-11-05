# `defineLayoutProperties`

This composable can be used to define the [properties](../../guide/pages-and-layouts.md#persistent-layout-properties) of the currently-defined persistent layout.

## Usage

```ts
function defineLayoutProperties<T>(properties: T): void
```

`defineLayoutProperties` accepts a single argument, an object that contains the layout properties. This function cannot be typed automatically.


## Example

The following example uses a layout using the template syntax, and define its properties in its script.

```vue
<script setup lang="ts">
defineLayoutProperties({ // [!code focus:3]
  fluid: true
})
</script>

<template layout="main"> // [!code focus]
  <!-- ... -->
</template>
```
