# Installation

Hybridly consists of a Laravel adapter, a Vue adapter and a Vite plugin. These last two are distributed together in the `hybridly` npm package.

You may install Hybridly in a fresh Laravel project using the preset. If you prefer, you can proceed to a manual installation. Alternatively, you can follow a detailed installation guide.

## Preset

:::warning
The preset is not yet available.
:::

## Server-side setup

This section is a summary of what's needed server-side, so that you can conveniently copy-paste snippets. For more thorough explanations, follow the [detailed guide](#detailed-installation-guide).

### Install the Laravel package

```bash
composer require hybridly/laravel
php artisan hybridly:install
```

### Create `root.blade.php`

```blade
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		@vite(['resources/css/app.css', 'resources/js/main.ts'])
	</head>
	<body class="antialiased">
		@hybridly
	</body>
</html>
```

You may also delete `welcome.blade.php`.

## Client-side setup

This section is a summary of what's needed client-side, so that you can conveniently copy-paste snippets. For more thorough explanations, follow the [detailed guide](#detailed-installation-guide).

### Install the dependencies

```shell
npm install hybridly vue axios@^1.0 @vitejs/plugin-vue -D
```

### Configure Vite

Rename `vite.config.js` to `vite.config.ts`, and import `@vitejs/plugin-vue` and `hybridly/vite` and register them as plugins.

```ts
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import laravel from 'laravel-vite-plugin'
import hybridly from 'hybridly/vite'

export default defineConfig({
	plugins: [
		laravel({
			input: ['resources/css/app.css', 'resources/js/main.ts'],
			valetTls: true
		}),
		hybridly(),
		vue(),
	],
})
```

### Initialize Hybridly

Delete `resources/js/app.js` and `resources/js/bootstrap.js`. In `resources/js/main.ts`, paste the following snippet:

```ts
import { createApp } from 'vue'
import { initializeHybridly } from 'hybridly/vue'
import 'virtual:hybridly/router'

initializeHybridly({
	cleanup: !import.meta.env.DEV,
	pages: import.meta.glob('../views/pages/**/*.vue', { eager: true }),
})
```

Use [`enhanceVue`](../api/utils/initialize-hybridly.md#enhancevue) to register plugins, components or directives.

### Add a `tsconfig.json`

```json
{
	"compilerOptions": {
		"target": "esnext",
		"module": "esnext",
		"moduleResolution": "node",
		"strict": true,
		"jsx": "preserve",
		"sourceMap": true,
		"resolveJsonModule": true,
		"esModuleInterop": true,
		"allowSyntheticDefaultImports": true,
		"lib": ["esnext", "dom"],
		"types": ["vite/client"],
		"baseUrl": ".",
		"paths": {
			"@/*": ["resources/*"]
		}
	},
	"vueCompilerOptions": {
		"experimentalSuppressInvalidJsxElementTypeErrors": true
	},
	"include": ["resources/**/*"],
	"exclude": ["public/**/*", "node_modules", "vendor"]
}
```

## Detailed installation guide

This little guide assumes you are using macOS with [Laravel Valet](https://laravel.com/docs/9.x/valet).

### Create and `cd` into the project

```bash
composer create-project laravel/laravel hybridly-app
cd hybridly-app
```

Install Node dependencies and navigate to `hybridly-app.test` to ensure the default installation is working properly.

```bash
pnpm install
open http://hybridly-app.test
```

At this point, you probably want to initialize your repository and create a first commit.

```bash
git init
git add .
git commit -m "chore: initialize project"
```

### Install the Laravel adapter

The first step is to install the Laravel adapter and setup the middleware.

```bash
composer require hybridly/laravel
php artisan hybridly:install
```

The last command extracted the middleware that is required to intercept responses and convert them to the Hybridly protocol. 

That middleware is, by default, located in `app/Http/Middleware/HandleHybridRequests.php`. It has automatically been registered into the `web` middleware group in `app/Http/Kernel.php`.

### Create `root.blade.php`

Hybridly needs a `root.blade.php` file that will be loaded on the first page render. The name is configurable, but you don't want to configure it. You want `root`. Not `app`. `root`.

Proceed to delete `welcome.blade.php` and create `root.blade.php`. It needs to contain the `@hybridly` directive and to load assets.

```blade
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		@vite(['resources/css/app.css', 'resources/js/main.ts'])
	</head>
	<body class="antialiased">
		@hybridly
	</body>
</html>
```

### Install the Vite plugin

The next step is to install and register the Vite plugin. It is, along with the Vue adapter, distributed in the `hybridly` package on npm.

We also need to install Vue, as long as the Vue plugin for Vite and the latest version of Axios.

```bash
npm install hybridly vue axios@^1.0 @vitejs/plugin-vue -D
```

At this point, your `package.json` should look like the following:

```json
{
		"private": true,
		"scripts": {
				"dev": "vite",
				"build": "vite build"
		},
		"devDependencies": {
				"@vitejs/plugin-vue": "^3.1.2",
				"axios": "^1.0",
				"hybridly": "0.0.1-alpha.3",
				"laravel-vite-plugin": "^0.6.0",
				"lodash": "^4.17.19",
				"postcss": "^8.1.14",
				"vite": "^3.0.0",
				"vue": "^3.2.41"
		}
}
```

Now is a good time to open `vite.config.js` and add the `hybridly` plugin. If you're feeling adventurous, and you probably are if you are reading this documentation, rename `vite.config.js` to `vite.config.ts`. Much better.

The Vite plugin is exported by `hybridly/vite`. Simply import it and add it to the list of plugins. I also recommend removing the default `refresh` parameter and adding `valetTls`.

Don't forget to also register the `vue` plugin, after `hybridly`. The order is important because `hybridly` has a layout plugin that depends on non-transformed SFCs.

```ts
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import laravel from 'laravel-vite-plugin'
import hybridly from 'hybridly/vite'

export default defineConfig({
	plugins: [
		laravel({
			input: ['resources/css/app.css', 'resources/js/main.ts'],
			valetTls: true
		}),
		hybridly(),
		vue(),
	],
})
```

If you add `valetTls`, which you should, don't forget to also run `valet secure` and set up `APP_URL` in `.env`.

### Initialize Hybridly

The Vite part is done, now you need to make your scripts aware of Hybridly. Rename `resources/js/app.js` to `resources/js/main.ts` and don't forget to update `vite.config.ts` accordingly. You can also delete `resources/js/bootstrap.js`, we don't need that.

In `main.ts`, paste the following snippet:

```ts
import { createApp } from 'vue'
import { initializeHybridly } from 'hybridly/vue'
import 'virtual:hybridly/router'

initializeHybridly({
	cleanup: !import.meta.env.DEV,
	pages: import.meta.glob('../views/pages/**/*.vue', { eager: true }),
  enhanceVue: (vue) => {}
})
```

The last import is what makes the `route` util typed. If you don't intend on using it because you prefer hardcoding URLs directly and having to replace them in your whole codebase when Quality Assurance need you to change pluralize your URLs, feel free to omit it.

- The `cleanup` property defines whether the properties should be removed from the DOM after the first load. This is not necessary but if we can do it, why not?

- The `pages` property must contain an object which keys are names of page components and values are the components themselves. `import.meta.glob` conveniently create that for us. Or maybe Hybridly adopter this format because `import.meta.glob` exists. Who knows.

- The `enhanceVue` property is *optional*. If provided, it must be a callback to which the Vue instance is given. You may register plugins, components or directives here.

You can read more about `initializeHybridly` in the [API documentation](../api/utils/initialize-hybridly.md).

### Add a `tsconfig.json`

Now, if you have keen eyes, you should have noticed that `import.meta` is yelling at you because `env` and `glob` don't exist. Mind you, they do exist - they're Vite things - but TypeScript doesn't know about it.

You guessed it, we need a `tsconfig.json`. Feel free to copy-paste the following and never look at it again:

```json
{
	"compilerOptions": {
		"target": "esnext",
		"module": "esnext",
		"moduleResolution": "node",
		"strict": true,
		"jsx": "preserve",
		"sourceMap": true,
		"resolveJsonModule": true,
		"esModuleInterop": true,
		"allowSyntheticDefaultImports": true,
		"lib": ["esnext", "dom"],
		"types": ["vite/client"],
		"baseUrl": ".",
		"paths": {
			"@/*": ["resources/*"]
		}
	},
	"vueCompilerOptions": {
		"experimentalSuppressInvalidJsxElementTypeErrors": true
	},
	"include": ["resources/**/*"],
	"exclude": ["public/**/*", "node_modules", "vendor"]
}
```

If you're interested in what each of these option do, feel free to look at the [TypeScript documentation](https://www.typescriptlang.org/tsconfig).

### Create a page component

It's almost done. We just need a page component and a route to serve it. Create a `resources/views/pages/index.vue` file. A page component is a standard Vue component, except it's now a page component.

```vue
<script setup lang="ts">
//
</script>

<template>
	<div>
		Hello Hybridly
	</div>
</template>
```

### Create a route

In `routes/web.php`, you can now instruct a route to load your new page component. You just need to give its name to the `hybridly` function.

```php
Route::get('/', function () {
		return hybridly('index');
});
```

### Start the development server

That's it. You only need to run `npm run dev` and open the application in your browser.

```shell
npm run dev
open https://hybridly-app.test
```

:::tip A preset is available
If you found that installation process tedious, consider using the [preset](#preset) for your next project.
:::

## What's next?

Hybridly has many features. You probably want to know how to pass data from the server to the front-end or how to navigate between pages.

Feel free to explore the whole documentation before committing to Hybridly. Have fun building your applications!
