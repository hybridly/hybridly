# Plugins

## Overview

Hybridly provides a simple plugin mechanism that allows for globally hooking into its lifecycle events.

This is what powers the built-in [progress indicator](./progress-indicator.md). This is the equivalent of Inertia's [global events](https://inertiajs.com/events), except it provides a structured way to extend its functionalities.

## Registering plugins

A plugin can be registered through the `plugins` property of the `initializeHybridly` function.

```ts
import { MyPlugin } from 'hybridly-plugin-something' // [!code focus]

initializeHybridly({
	cleanup: !import.meta.env.DEV,
	pages: import.meta.glob('@/views/pages/**/*.vue', { eager: true }),
	plugins: [ // [!code focus:3]
		MyPlugin()
	],
	setup: ({ render, element, hybridly }) => createApp({ render })
		.use(hybridly)
		.mount(element),
})
```

## Developing plugins

A plugin is a simple object with at least a `name` property. For convenience, Hybridly exports a `definePlugin` function that provides typings for plugins.

Aside from hooking into lifecycle events, plugins can detect when Hybridly is initialized through to the `initialized` hook.

```ts
import { definePlugin } from 'hybridly'

export default function MyPlugin(options?: MyPluginOptions) {
  return definePlugin({
    name: 'hybridly:my-plugin',
    initialized(context) {
      console.log('Hybridly has been initialized')
    },
    // Other lifecycle hooks
  })
}

export interface MyPluginOptions {
  // ...
}
```
