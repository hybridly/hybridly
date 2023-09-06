# `can`

This helper function may be used with a [`DataResource`](../../guide/authorization.md#using-data-resources) to determine its authorizations.

| Related | [Authorization](../../guide/authorization.md) |
| ------- | --------------------------------------------- |

## Usage

```ts
function can(resource: Authorizable, action: Action): boolean
```

`can` takes two arguments: an object that implements `Authorizable` and a string that's an action from this `Authorizable`.

Data objects that extend `DataResource` and transformed to TypeScript interfaces implement `Authorizable`.

To learn more about generating TypeScript interfaces, read the [TypeScript setup](../../guide/typescript.md) and the [authorization](../../guide/authorization.md) documentation.

## Example

This example assumes that a `ChirpData` object extending `DataResource` is defined and converted to a TypeScript interface.

```ts
<script setup lang="ts">
import { can } from 'hybridly' // [!code focus]

const $props = defineProps<{
  chirp: App.Data.ChirpData  // [!code focus]
}>()

const canComment = can($props.chirp, 'comment')  // [!code focus]
</script>
```
