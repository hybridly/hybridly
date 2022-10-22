# `defineLayout`

This composable can be used to define the current [persistent layout](../../guide/pages-and-layouts.md#persistent-layouts), and, optionally, its [properties](../../guide/pages-and-layouts.md#persistent-layout-properties).

## Usage

```ts
function defineLayout(layout: Layout, properties?: T): void
function defineLayout(layouts: Layout[]): void
```

`defineLayouts` accepts either a layout as its first argument and its properties as the second, or an array of layouts as its single argument.


## Example

The following example imports a layout component and defines it as the current persistent layout.

```vue
<script setup lang="ts">
import profile from '@/views/layouts/profile.vue'

defineLayout(profile)
</script>
```
