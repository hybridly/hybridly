# `defineLayoutProperties`

This function can be used to define the [properties](../../guide/pages-and-layouts.md#persistent-layout-properties) of the currently-defined persistent layout.

| Related                                           | [`defineLayout`](./define-layout.md)                  |
| ------------------------------------------------- | ----------------------------------------------------- |
| Experimental{class="font-medium text-orange-200"} | This function can be changed or removed at any point. |

## Usage

`defineLayoutProperties` accepts a single argument, an object that contains the layout properties. This function cannot be typed automatically.

:::code-group
```vue [pages/index.vue]
<script setup lang="ts">
defineLayoutProperties({ // [!code focus:3]
  fluid: true
})
</script>

<template layout="main"> // [!code focus]
  <!-- ... -->
</template>
```

```vue [layouts/main.vue]
<script setup lang="ts">
defineProps<{ // [!code focus:3]
  fluid: boolean
}>()
</script>

<template>
  <main :class="{  // [!code focus:6]
    'w-full': fluid,
    'container mx-auto': !fluid
  }">
    <!-- ... -->
  </main>
</template>
```
:::

The example above uses a layout using the template syntax, and define its properties in its script.
