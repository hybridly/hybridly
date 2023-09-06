---
outline: deep
---

# `setProperty`

This function updates the given property. The path is typed, provided [TypeScript support for global properties](../../guide/global-properties.md#typescript-support) is set up properly.

| Related                                           | [`useProperty`](./use-property.md), [`useProperties`](./use-properties.md) |
| ------------------------------------------------- | -------------------------------------------------------------------------- |
| Experimental{class="font-medium text-orange-200"} | This function can be changed or removed at any point.                      |

## Usage

`setProperty` accepts the property name as its first parameter and the property value as its second. To update a nested property, you may use a dot-notated path.

### Global properties

```ts
const name = useProperty('security.user.full_name')
console.log(name) // Jon Doe

setProperty('security.user.full_name', 'Jane Doe')
console.log(name) // Jane Doe
```

### Local properties

Since local properties can't benefit from global typings, you may use a generic to specify its type.

```ts
const $props = defineProps<{ users: number }>()
console.log($props.users) // 41

setProperty<number>('users', 42)
console.log($props.users) // 42
```
