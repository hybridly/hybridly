---
outline: deep
---

# `setProperty`

<p class="preface">
This function updates the given property. The path is typed, provided <a href="../../guide/global-properties.md">TypeScript support for global properties</a> is set up properly.
</p>

## Usage

`setProperty` accepts the property name as its first parameter and the property value as its second. To update a nested property, you may use a dot-notated path.

:::tip Advanced API
In most cases, you should use [partial reloads](../../guide/partial-reloads.md) instead.
:::

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
