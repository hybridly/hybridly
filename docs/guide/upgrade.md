# Upgrade guide

This guide describes how to upgrade from `v0.0.1-alpha` to `v0.1.0`.

## Moving pages and layouts

- **Likelihood of impact**: high

Previously, the expected emplacement for both page components and layouts was in `resources/views`. This has now moved to `resources`, so you should move them here.

```diff
  resources/
- ├── views/
- │   ├── layouts/
- │   │   └── default.vue
- │   └── pages/
- │       ├── index.vue
- │       └── security/
- │           └── login.vue
+ ├── layouts/
+ │   └── default.vue
+ └── pages/
+     ├── index.vue
+     └── security/
+         └── login.vue
```

## Updating `tsconfig.json`

- **Likelihood of impact**: low

A pre-configured `tsconfig.json` file is generated when the development server starts.

## Moving `root.blade.php`

- **Likelihood of impact**: low

Previously, the recommended emplacement for `root.blade.php` was in `resources/views`. This has now moved to `resources/application`, so you may move it or keep it where it was.
