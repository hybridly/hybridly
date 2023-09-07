# `useHistoryState`

This function can reactively save the given value into the history state. The state is tied to a single [history entry](https://developer.mozilla.org/en-US/docs/Web/API/History).

## Usage

`useHistoryState` accepts a key as its first argument and an initial value as its second. The key is an identifier for storing the initial value. 

The function returns the saved state if it exists, or the the initial value otherwise.

```vue
<script setup lang="ts">
const name = useHistoryState('name', '')
</script>

<template>
  <input type="text" v-model="name" />
</template>
```

The example above will save a `name` property in the history state, only for the current browing history entry. When navigating back and then forward, the entry will be restored. When refreshing, the entry will have disappeared.
