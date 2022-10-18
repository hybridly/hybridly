# Progress indicator

## Overview

Single-page applications, because of their nature, do not benefit from the browser loading indicator. When pages take time to load, it can look like a navigation did not work, whereas it is in progress in the background.

To solve this, a progress indicator is required. Hybridly comes with one by default, but you can disable it and implement your own.

## Built-in indicator

By default, a progress indicator is shown when a request takes longer than 250 milliseconds to finish.

You may customize this behavior by providing a `progress` property to the `initializeHybridly` function.

```ts
initializeHybridly({
	cleanup: !import.meta.env.DEV,
	pages: import.meta.glob('@/views/pages/**/*.vue', { eager: true }),
	progress: { // The default options are as follow: // [!vp focus:6]
		color: '#fca5a5',
		delay: 250,
		includeCSS: true,
		spinner: false,
	},
	setup: ({ render, element, hybridly }) => createApp({ render })
		.use(createHead())
		.use(autoAnimate)
		.use(hybridly)
		.mount(element),
})
```

:::info Credits where due
The underlying progress indicator uses [nprogress](https://ricostacruz.com/nprogress/). The original implementation comes from [`@inertiajs/progress`](https://github.com/inertiajs/progress).
:::

## Custom indicator

Under the hood, the built-in progress indicator is actually a [plugin](./plugins.md). It hooks into the `start`, `progress`, `error`, `fail` and `after` [lifecycle events](./hooks.md).

To build your own custom indicator, disable the built-in one and [create your own plugin](./plugins.md).
