---
outline: 'deep'
---

# `useDialog`

This util returns functions which control [dialogs](../../guide/dialogs.md).

## Usage

`useDialog` doesn't accept any option. 

```ts
const { show, close, unmount } = useDialog()
```

It returns a `close` and `unmount` function which control the currently displayed dialog, as well as a `show` property that defines whether the dialog should be shown.


## Options

### `show`

- **Type**: `Computed<boolean>`

Defines whether the dialog should be shown. Generally, it is used to control the transition components wrapping the dialog.

### `close`

- **Type**: `() => void`

Closes the dialog, effectively assigning `false` to `show`.

### `unmount`

- **Type**: `() => void`

Removes the dialog component from the DOM. It should be called after all transitions are finished, generally as a callback to the `after-leave` transition event.
