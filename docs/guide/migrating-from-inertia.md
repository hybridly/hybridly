# Migrating from Inertia

Inertia and Hybridly share a lot in common, so the refactor is a matter of changing the syntax to Hybridly's API, renaming imports and making a few other tweaks.

## Easy changes first

We recommend cleaning up dependencies and making the obvious changes before anything else.

**1.** First, remove some Composer and NPM packages:

```bash
composer remove tightenco/ziggy
composer remove inertiajs/inertia-laravel
npm uninstall \
  @inertiajs/progress @inertiajs/inertia @inertiajs/inertia-vue3 \
  ziggy-js @types/ziggy-js
```

**2.** Remove the following files if you have them:

```diff
- app/Console/Commands/GenerateZiggyRoutes.php
- app/Http/Middleware/HandleInertiaRequests.php
- config/inertia.php
- resources/scripts/inertia/layout.ts
```

**3.** Globally replace `Inertia::render` and `inertia` calls to use the `hybridly` function.

**4.** Replace the `@inertia` directive with `@hybridly`.

## Other changes

The easiest part done, you will need to install Hybridly by following the [installation](./installation.md) guide. 

The next step will be to find calls to `Inertia.get`, `useForm` and similar functions and replace them by their Hybridly equivalent.

On the back-end, you may copy over the content of `HandleInertiaRequests` to `HandleHybridlyRequests` as you see fit.


## Guide: moving PingCRM to Hybridly

> TODO
