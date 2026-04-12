---
outline: "deep"
---

# `useProperties`

<p class="preface">
This composable returns a reactive object containing all global and local properties, with typing support for globals.
</p>

## Usage

`useProperties` does not accept any argument, and returns a `reactive` value of all properties, global and local.

```ts
const properties = useProperties()
```

### Typing extra properties

Since local properties can not be inferred, the function accepts a generic argument for typing them.

```ts
const properties = useProperties<{
	foo: string
}>()

console.log('foo')
```
