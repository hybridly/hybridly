# Hooks

## Overview

Hybridly's requests have their own lifecycle. It's sometimes necessary to hook into some of its events to perform custom logic.

For instance, the progress bar is implemented using these hooks.

There are a two main ways to catch Hybridly's events: globally, through [plugins](./plugins.md), or locally, through [visit options](../api/router/utils.md).

## Plugins

Plugins can be registered through [`initializeHybridly`](../api/utils/initialize-hybridly.md)'s `plugins` option. They have access to all [request lifecycle events](#request-lifecycle-events), plus a few additionnal [plugin-specific hooks](./plugins.md#plugin-specific-hooks).

They are useful when you need to execute custom logic for each request. You can learn more about plugins in [their documentation](./plugins.md).

## Registering hooks manually

Though it's highly recommended to use plugins, it's also possible to register hooks with the `registerHook` function.

This may be useful for requirements for which plugins could be overkill.

```ts
<script setup lang="ts">
registerHook('navigated', ({ isBackForward }) => { // [!code focus:5]
  if (isBackForward) {
    router.reload()
  }
})
</script>
```

## Visit options

When [navigating](./navigation.md) or using the [form util](./forms.md), it's possible to pass a `hook` object that accepts a callback for each lifecycle event.

These callbacks will be executed just once for the current request. You can learn more about visit options in [their documentation](../api/router/utils.md).

## Request lifecycle events

The following lifecycle events can be hooked into when making a request, via the [`hooks`](../api/router/options.md#hooks) navigation option. They may be awaited if necessary.

<<< ./packages/core/src/plugins/hooks.ts#requesthooks
