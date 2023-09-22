# Progress indicator

## Overview

Single-page applications, because of their nature, do not benefit from the browser loading indicator. When pages take time to load, it can look like a navigation did not work, whereas it is in progress in the background.

To solve this, a progress indicator is required. Hybridly comes with one by default, but you can disable it and implement your own.

## Using the built-in indicator

By default, a progress indicator is shown when a request takes longer than 250 milliseconds to finish.

You may customize this behavior by providing a `progress` property to the `initializeHybridly` function.

```ts
initializeHybridly({
	progress: { // The default options are as follow: // [!code focus:6]
		color: '#fca5a5',
		delay: 250,
		includeCSS: true,
		spinner: false,
	},
})
```

:::info Credits where due
The underlying progress indicator uses [nprogress](https://ricostacruz.com/nprogress/). The original implementation is inspired from [`@inertiajs/progress`](https://github.com/inertiajs/progress).
:::

## Using a custom indicator

Under the hood, the built-in progress indicator is actually a [plugin](./plugins.md). It hooks into the `start`, `progress`, `error`, `fail` and `after` [lifecycle events](./hooks.md).

To build your own custom indicator, disable the built-in one and [create your own plugin](./plugins.md).
