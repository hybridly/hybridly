# `initializeHybridly`

This function must be invoked in an entry file to initialize Hybridly's context and router.

## Example

The following snippet is a typical example of how Hybridly could be set up.

```ts
import { createApp } from 'vue'
import { initializeHybridly } from 'hybridly/vue'
import 'virtual:hybridly/router'

initializeHybridly({
	pages: import.meta.glob('@/views/pages/**/*.vue', { eager: true }),
})
```

## `id`

- **Type**: `string`

Defines the `id` of the element the Vue application should be mounted on. When changing this value to something else than `root`, it must be updated in the [directive](../laravel/directives.md#id) as well.

## `pages`

- **Type**: `Record<string, Component>`

Defines the collection of pages Hybridly should be aware of when navigating. This is a shortcut for defining a `resolve` function. Typically, it accepts the result of Vite's `import.meta.glob`.

## `enhanceVue`

- **Type**: `(vue: App) => MaybePromise<void>`

Defines a callback that receives the Vue instance as a parameter and gets executed before Vue is mounted. 

This can be used to register additionnal Vue plugins, directives or components.

### Example

```ts
import { createApp } from 'vue'
import { createHead } from '@vueuse/head'
import { autoAnimatePlugin as autoAnimate } from '@formkit/auto-animate/vue'
import { initializeHybridly } from 'hybridly/vue'
import 'virtual:hybridly/router'

initializeHybridly({
	pages: import.meta.glob('@/views/pages/**/*.vue', { eager: true }),
	enhanceVue: (vue) => vue // [!code focus:3]
		.use(createHead())
		.use(autoAnimate),
})
```

## `setup`

- **Type**: `(options: SetupArguments) => MaybePromise<void>`

Defines a callback that overrides how the Vue application will be created. It accepts an object with the necessary properties to set up Hybridly:

- `element`: the DOM element to which Vue should be mounted on
- `wrapper`: the wrapper component responsible for Hybridly to work
- `render`: the render function that should be passed to `createApp`
- `hybridly`: the Vue plugin that sets up Vue DevTools

By default, `setup` is optional because the Vue application is created under the hood. It can be enhanced through [`enhanceVue`](#enhancevue).

### Example

```ts
import { createApp } from 'vue'
import { initializeHybridly } from 'hybridly/vue'
import 'virtual:hybridly/router'

initializeHybridly({
	pages: import.meta.glob('@/views/pages/**/*.vue', { eager: true }),
	setup: ({ render, element, hybridly }) => createApp({ render }) // [!code focus:3]
		.use(hybridly)
		.mount(element),
})
```

## `layout`

- **Type**: `Component`

Defines the default persistent layout.

## `resolve`

- **Type**: `(name: string) => Component`

Defines the function that is used to find a component based on its name. 

By default, the resolver uses the same syntax as Blade's (eg. `users.index` instead of `Users/Index` when using Inertia).

## `serializer`

- **Type**: `{ serialize: (data: object) => object; unserialize: (data: object) => object}`

Provides custom serialization functions that are used when saving and loading data from the history state.

By default, the state is serialized using `JSON.parse(JSON.stringify(data))`, and unserialized as-is.

## `cleanup`

- **Type**: `bool`
- **Required**: true

Defines whether to remove the `data-payload` attribute from the generated element. Note that this is not a security measure, but an aesthetic (and quite useless) one.

## `progress`

- **Type**: `boolean` or `ProgressOptions`

When set to `false`, disables the built-in progress indicator. Otherwise, configures it. Refer to the [progress indicator](../../guide/progress-indicator.md) documentation for more information.

## `plugins`

- **Type**: `Plugin[]`

Defines the plugins that should be registered. Refer to the [plugin documentation](../../guide/plugins.md) to learn more about them.

## `axios`

- **Type**: `Axios`

Defines a custom Axios instance that will replace the one Hybridly would internally use otherwise.

```ts
import { initializeHybridly } from 'hybridly/vue'
import axios from 'axios'
import 'virtual:hybridly/router'

initializeHybridly({
	pages: import.meta.glob('@/views/pages/**/*.vue', { eager: true }),
	axios: axios.create({ // [!code focus:5]
		headers: {
			'X-Custom-Header': 'value',
		},
	}),
})
```
