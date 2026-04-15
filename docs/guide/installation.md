# Installation

Hybridly consists of a Laravel adapter, a Vue adapter and a Vite plugin. These last two are distributed together in the `hybridly` npm package.

The simplest way to get started with Hybridly is to use the preset in a fresh Laravel project. Alternatively, you can proceed to a manual installation following this guide.

## Preset

The recommended way of installing Hybridly is to use the preset in a [fresh Laravel project](https://laravel.com/docs/installation). Run the following command in the root of your project:

:::code-group

```bash [bun]
bunx @preset/cli apply hybridly/preset
```

```bash [pnpm]
pnpm dlx @preset/cli apply hybridly/preset
```

```bash [yarn]
yarn dlx @preset/cli apply hybridly/preset
```

```bash [npm]
npx @preset/cli apply hybridly/preset
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
```

### Create `root.blade.php` in `resources`

:::code-group

```html [resources/root.blade.php]
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		@vite
	</head>
	<body class="antialiased">
		@hybridly
	</body>
</html>
```

:::

## Use `HandleHybridRequests` and return hybrid views

:::code-group

```php [app/Users/ShowUserController.php]
use App\Users\User;

use function Hybridly\view;

final readonly class ShowUserController
{
    public function __invoke(User $user): HybridResponse
    {
        return view('users.show', [
            'user' => $user
        ]);
    }
}
```

```php [routes/web.php]
Route::get('/users/{user}', ShowUserController::class)
  ->middleware(HandleHybridRequests::class) // [!code hl]
  ->name('users.show');
```

:::

## Client-side setup

This section is a summary of what's needed client-side, so that you can conveniently copy-paste snippets. For more thorough explanations, follow the [detailed guide](#detailed-installation-guide).

### Install the dependencies

:::code-group

```bash [ni]
ni hybridly vue -D
```

```bash [bun]
bun i hybridly vue -D
```

```bash [pnpm]
pnpm i hybridly vue -D
```

```bash [npm]
npm i hybridly vue -D
```

```bash [yarn]
yarn add hybridly vue -D
```

:::

### Configure Vite

Rename `vite.config.js` to `vite.config.ts`, and register `hybridly/vite` as a plugin.

You may uninstall `@laravel/vite-plugin`, which functionality is included in the Hybridly plugin, as well as `axios`.

:::code-group

```ts [vite.config.ts]
import tailwindcss from '@tailwindcss/vite'
import hybridly from 'hybridly/vite'
import { defineConfig } from 'vite'

export default defineConfig({
	plugins: [
		hybridly(),
		tailwindcss(),
	],
})
```

:::

### Initialize Hybridly

Delete the `resources/js` and `resources/css` directories, then create `resources/main.ts` and `resources/main.ts` with the following snippet:

:::code-group

```ts [resources/main.ts]
import { initializeHybridly } from 'virtual:hybridly/config'
import './main.css'

initializeHybridly()
```

```css [resources/main.css]
@import "tailwindcss";
```

:::

Use [`enhanceVue`](../api/utils/initialize-hybridly.md#enhancevue) to register plugins, components or directives.

### Add a `tsconfig.json`

```json
{
	"files": [],
	"references": [
		{ "path": "./.hybridly/tsconfig.json" },
		{ "path": "./.hybridly/tsconfig.node.json" }
	]
}
```

Note that until the development server is started, this may cause errors in your editor, as the file it references does not exist yet.

## Detailed installation guide

This little guide assumes you are using macOS with [Laravel Valet](https://laravel.com/docs/13.x/valet) or [Herd](https://laravel.com/docs/13.x/installation#installation-using-herd).

### Create and `cd` into the project

Create the project using `composer create-project` or the Laravel installer, navigate to the project directory and install front-end dependencies.

```bash
composer create-project laravel/laravel hybridly-app
cd hybridly-app
bun install
```

At this point, you probably want to initialize your repository and create a first commit.

```bash
git init
git add .
git commit -m "chore: initialize project"
```

### Install the Laravel adapter

The first step is to install the Laravel adapter.

```bash
composer require hybridly/laravel
```

### Create `root.blade.php`

Hybridly needs a `root.blade.php` file that will be loaded on the initial page load. The name and path are configurable, but it's a good default.

Proceed to delete everyting in `resources`, and create `resources/root.blade.php`. It needs to contain the `@vite` directive and to load assets, as well as the `@hybridly` directive to create the element that will host the Vue application.

```html
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		@vite
	</head>
	<body class="antialiased">
		@hybridly
	</body>
</html>
```

### Install the Vite plugin

The next step is to install and register the Vite plugin. It is, along with the Vue adapter, distributed in the `hybridly` package on [npm](https://npmx.dev/package/hybridly).

We also need to install `vue`, and uninstall `axios`, which is not needed.

```bash
bun install hybridly vue -D
bun uninstall axios
```

At this point, your `package.json` should look like the following:

```json
{
	"$schema": "https://www.schemastore.org/package.json",
	"private": true,
	"type": "module",
	"scripts": {
		"build": "vite build",
		"dev": "vite"
	},
	"devDependencies": {
		"@tailwindcss/vite": "^4.0.0",
		"axios": ">=1.11.0 <=1.14.0",
		"concurrently": "^9.0.1",
		"hybridly": "^0.9.0",
		"laravel-vite-plugin": "^3.0.0",
		"tailwindcss": "^4.0.0",
		"vite": "^8.0.0",
		"vue": "^3.5.32"
	}
}
```

Now is a good time to rename `vite.config.js` to `vite.config.ts` (because we like types) and register the `hybridly` plugin, exported by `hybridly/vite`. The `laravel-vite-plugin` can be removed and uninstalled.

```ts
import hybridly from 'hybridly/vite' // [!code ++]
import laravel from 'laravel-vite-plugin' // [!code --]
import { defineConfig } from 'vite'

export default defineConfig({
	plugins: [
		laravel({ // [!code --]
			input: ['resources/css/app.css', 'resources/js/app.js'], // [!code --]
			refresh: true, // [!code --]
		}), // [!code --]
		hybridly(), // [!code ++]
	],
})
```

### Initialize Hybridly

The Vite part is done, now you need to make your scripts aware of Hybridly. Create `resources/main.ts`, which should contain the following snippet:

```ts
import { initializeHybridly } from 'virtual:hybridly/config'

initializeHybridly({
	enhanceVue: (vue) => {},
})
```

You can read more about `initializeHybridly` in the [API documentation](../api/utils/initialize-hybridly.md).

The [`enhanceVue`](../api/utils/initialize-hybridly.md#enhancevue) property is optional, but you will most likely need it to register plugins such as [`unhead`](https://unhead.unjs.io/docs/vue/head/guides/get-started/installation).

You may also have a type error on the `virtual:hybridly/config` import, which is going to be away in the next section.

### Add a `tsconfig.json`

Your project needs a `tsconfig.json` file to understand objects such as `import.meta` or imports like `virtual:hybridly/config`. The complicated part of this file is automatically generated by Hybridly when the development server is started, so you can create your own simple `tsconfig.json` that references it:

```json
{
	"files": [],
	"references": [
		{ "path": "./.hybridly/tsconfig.json" },
		{ "path": "./.hybridly/tsconfig.node.json" }
	]
}
```

The [TypeScript documentation](https://www.typescriptlang.org/tsconfig) is a good place to understand the configuration options that are available in this file.

### Ignore `.hybridly`

Add `.hybridly` to your `.gitignore`.

This directory will be generated by the development server and contain diverse files such as the main `tsconfig.json`, localization files, and diverse TypeScript declaration files.

### Create a view component

It's almost done. We just need a view component and a route to serve it. Create a `resources/index.view.vue` file. A view component is a standard Vue component, except it's now a view component.

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

In `routes/web.php`, you can now instruct a route to load your new view component. You just need to give its name to Hybridly's [`view`](../api/laravel/functions.md#view) function.

```php
use function Hybridly\view;

Route::get('/', function () {
		return view('index');
});
```

### Configure `.env`

If not already done, copy `.env.example` to `.env` and configure it. Run `php artisan key:generate` and, more importantly, update `APP_URL` to the URL that will serve your application. Hybridly requires it to be accurate to generate URLs.

If you use `php artisan serve`, set it to `http://127.0.0.1:8000`. Otherwise, refer to the documentation of your web server.

### Start the development server

That's it. You only need to run `bun run dev` and open the application in your browser.

```shell
composer dev
open http://127.0.0.1:8000
```

### Troubleshooting

In some cases, you might see a blank page. To fix it, open the URL displayed in your browser console in a separate tab, and accept its certificate.

## What's next?

You may want to look into setting up [PHP to TypeScript transformation](../guide/typescript.md).

To get started with building your application, you should read how to [pass data from the server](../guide/responses.md) to the front-end or how to [navigate between views](../guide/navigation.md).

Feel free to explore the whole documentation before committing to Hybridly. Have fun building your applications!
