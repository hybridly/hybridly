# Migrating from Inertia

## Overview

Inertia works very similarly as Hybridly, so the migration is mostly dependency and syntax changes.

Both APIs are very similar, but you will need to rename imports, adapt to slightly different syntaxes, make a few architectural changes (mostly for front-end files), and a few other minor tweaks.

You are welcome to ask for help on our [Discord server](https://discord.gg/uZ8eC7kRFV) if you find any issue.

## Dependency changes

We recommend cleaning up dependencies and making the obvious changes before anything else.

First, remove some Composer and npm packages:

```bash
composer remove tightenco/ziggy
composer remove inertiajs/inertia-laravel
npm uninstall \
  @inertiajs/progress @inertiajs/inertia @inertiajs/inertia-vue3 \
  ziggy-js @types/ziggy-js
```

You may also remove the following files if you have them:

```diff
- app/Console/Commands/GenerateZiggyRoutes.php
- config/inertia.php
```

## API changes

The easiest part done, you will need to install Hybridly by following the [installation](./installation.md) guide. 

The next step will be to find Inertia APIs and replace them with their Hybridly equivalents. Here's a non-exhaustive list of things to change: 
- Moving `resources/views/app.blade.php` to `resources/application/root.blade.php`
  - Replacing the `@inertia` directive with `@hybridly`
- Moving `resources/js/app.js` to `resources/application/main.ts`
  - Replacing the `createInertiaApp` initialization function with [`initializeHybridly`](../api/utils/initialize-hybridly.md)
- Moving `HandleInertiaRequests.php` to [`HandleHybridRequests.php`](./global-properties.md#from-the-middleware)
- Replacing `Inertia::render` and `inertia` calls with [`hybridly`](../api/laravel/hybridly.md) or [`view`](../api/laravel/functions.md#view) ones
- Replacing `router.*` or `Inertia.*` calls with our similar [navigation syntax](./navigation.md)
- Adapting `useForm` usage with our own [form syntax](./forms.md)
- Replacing `<Head>` component with [`@unhead/vue`](./title-and-meta.md)'s `useHead`
