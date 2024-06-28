# Installation

Hybridly consists of a Laravel adapter, a Vue adapter and a Vite plugin. These last two are distributed together in the `hybridly` npm package.

The simplest way to get started with Hybridly is to use the preset in a fresh Laravel project. Alternatively, you can proceed to a manual installation following this guide.

## Preset

The recommended way of installing Hybridly is to use the preset in a [fresh Laravel project](https://laravel.com/docs/installation). Run the following command in the root of your project:

:::code-group
```bash [npm]
npx @preset/cli apply hybridly/preset
```
```bash [pnpm]
pnpm dlx @preset/cli apply hybridly/preset
```
```bash [bun]
bunx @preset/cli apply hybridly/preset
```
```bash [yarn]
yarn dlx @preset/cli apply hybridly/preset
```
:::

The preset automatically sets up [Tailwind CSS](https://tailwindcss.com) and [Pest](https://pestphp.com). You may add any of the following flags to the previous command to customize the preset:

| Flag          | Description                                                                           |
| ------------- | ------------------------------------------------------------------------------------- |
| `--i18n`      | Install and setup [**vue-i18n**](https://vue-i18n.intlify.dev/)                       |
| `--no-pest`   | Do not setup [**Pest**](https://pestphp.com/)                                         |
| `--no-strict` | Do not setup Laravel strict mode                                                      |
| `--no-ide`    | Do not setup [**laravel-ide-helper**](https://github.com/barryvdh/laravel-ide-helper) |

More information about the preset can be found on its [**repository**](https://github.com/hybridly/preset).

:::info Installation
Once you have installed the preset, **you do not need to follow the rest of the installation guide**. Hybridly is already installed.
:::


## Server-side setup

This section is a summary of what's needed server-side, so that you can conveniently copy-paste snippets. For more thorough explanations, follow the [detailed guide](#detailed-installation-guide).

### Install the Laravel package

```bash
composer require hybridly/laravel
php artisan hybridly:install
```

### Create `root.blade.php` in `resources/application`

```blade
<!-- resources/application/root.blade.php -->
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		@vite(['resources/css/app.css', 'resources/application/main.ts'])
	</head>
	<body class="antialiased">
		@hybridly
	</body>
</html>
```

## Client-side setup

This section is a summary of what's needed client-side, so that you can conveniently copy-paste snippets. For more thorough explanations, follow the [detailed guide](#detailed-installation-guide).

### Install the dependencies

:::code-group
```bash [ni]
ni hybridly vue axios -D
```
```bash [pnpm]
pnpm i hybridly vue axios -D
```
```bash [bun]
bun i hybridly vue axios -D
```
```bash [npm]
npm i hybridly vue axios -D
```
```bash [yarn]
yarn add hybridly vue axios -D
```
:::

### Configure Vite

Rename `vite.config.js` to `vite.config.ts`, and register `hybridly/vite` as a plugin.

```ts
import { defineConfig } from 'vite'
import hybridly from 'hybridly/vite'

export default defineConfig({
	plugins: [
		hybridly({
			laravel: {
				detectTls: true
			}
		}),
	],
})
```

### Initialize Hybridly

Delete the `resources/js` directory, and create a `resources/application/main.ts` file with the following snippet:

```ts
import { initializeHybridly } from 'virtual:hybridly/config'

initializeHybridly()
```

Use [`enhanceVue`](../api/utils/initialize-hybridly.md#enhancevue) to register plugins, components or directives.

### Add a `tsconfig.json`

```json
{
	"extends": "./.hybridly/tsconfig.json"
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

Hybridly needs a `root.blade.php` file that will be loaded on the initial page load. The name and path are configurable, but it's a good default.

Proceed to delete `resources/views`, and create `resources/application/root.blade.php`. It needs to contain the `@hybridly` directive and to load assets.

```blade
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		@vite(['resources/css/app.css', 'resources/application/main.ts'])
	</head>
	<body class="antialiased">
		@hybridly
	</body>
</html>
```

### Install the Vite plugin

The next step is to install and register the Vite plugin. It is, along with the Vue adapter, distributed in the `hybridly` package on npm.

We also need to install Vue and the latest version of Axios.

```bash
npm install hybridly vue axios -D
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
				"axios": "^1.7.2",
				"hybridly": "0.7.3",
				"lodash": "^4.17.19",
				"postcss": "^8.1.14",
				"vite": "^5.0.8",
				"vue": "^3.2.41"
		}
}
```

Now is a good time to rename `vite.config.js` to `vite.config.ts` and register the `hybridly` plugin, exported by `hybridly/vite`.

```ts
import { defineConfig } from 'vite'
import hybridly from 'hybridly/vite'

export default defineConfig({
	plugins: [
		hybridly({
			laravel: {
				detectTls: true
			}
		}),
	],
})
```

If you add `detectTls`, which you should, don't forget to also run `valet secure` and set up `APP_URL` in `.env`.

### Initialize Hybridly

The Vite part is done, now you need to make your scripts aware of Hybridly. Delete the `resources/js` directory and create `resources/application/main.ts`, which should contain the following snippet:

```ts
import { createApp } from 'vue'
import { initializeHybridly } from 'virtual:hybridly/config'

initializeHybridly({
	enhanceVue: (vue) => {}
})
```

You can read more about `initializeHybridly` in the [API documentation](../api/utils/initialize-hybridly.md).

### Add a `tsconfig.json`

Your project needs a `tsconfig.json` file to understand objects such as `import.meta` or imports like `virtual:hybridly/config`. The complicated part of this file is automatically generated by Hybridly when the development server is started, so you can create your own simple `tsconfig.json` that extends it:

```json
{
	"extends": "./.hybridly/tsconfig.json"
}
```

The [TypeScript documentation](https://www.typescriptlang.org/tsconfig) is a good place to understand the configuration options that are available in this file.

### Create a view component

It's almost done. We just need a view component and a route to serve it. Create a `resources/views/index.vue` file. A view component is a standard Vue component, except it's now a view component.

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

In `routes/web.php`, you can now instruct a route to load your new view component. You just need to give its name to the `hybridly` function.

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

## What's next?

You may want to look into setting up [PHP to TypeScript transformation](../guide/typescript.md).

To get started with building your application, you should read how to [pass data from the server](../guide/responses.md) to the front-end or how to [navigate between views](../guide/navigation.md).

Feel free to explore the whole documentation before committing to Hybridly. Have fun building your applications!
