# `defineLayout`

This function can be used to define the current [persistent layout](../../guide/views-and-layouts.md#persistent-layouts), and, optionally, its properties.

| Related                                           | [`defineLayoutProperties`](./define-layout-properties.md) |
| ------------------------------------------------- | --------------------------------------------------------- |
| Experimental{class="font-medium text-orange-200"} | This function can be changed or removed at any point.     |

## Usage

```ts
function defineLayout(layout: Layout, properties?: T): void
function defineLayout(layouts: Layout[]): void
```

`defineLayouts` accepts either a layout as its first argument and its properties as the second, or an array of layouts as its single argument.

```vue
<script setup lang="ts">
import main from '@/views/layouts/main.vue' // [!code focus:5]

defineLayout(profile, {
  fluid: false
})
</script>
```
