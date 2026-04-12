---
outline: deep
---

# `useProperty`

<p class="preface">
This function returns a property as a <code>Ref</code> from a dot-notated path, with typing support when global property typings are configured.
</p>

## Usage

`useProperty` accepts a dot-notated path as its first parameter and returns a `ComputedRef` with the value at the given path.

```ts
const name = useProperty('security.user.full_name')

useHead({
	title: () => `${name.value}'s profile`,
})
```

### Accessing view properties

While `useProperty` is primarily made for accessing typed, global properties, you may provide a custom generic type to opt-out of global type-checking if you need to access view-specific properties.

```ts
const posts = useProperty<App.Data.Post[]>('posts')
```
