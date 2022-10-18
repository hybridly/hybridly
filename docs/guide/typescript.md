# TypeScript

## Overview

Hybridly aims to provide typings whenever it's possible. While the core and the front-end adapters of Hybridly are written with TypeScript and always provide definitions, code from the back-end must still be transformed to types in order to benefit from autocompletion while staying [DRY](https://en.wikipedia.org/wiki/Don%27t_repeat_yourself).

This could be a real challenger — fortunately, there are existing solutions to transform PHP classes and enums to TypeScript types and interfaces. Specifically, Hybridly provides back-end typings through [Data](https://github.com/spatie/laravel-data) and [TypeScript Transformer](https://github.com/spatie/laravel-typescript-transformer).

:::info Using the preset?
Note that if you scaffolded an application using the [provided preset](./installation.md#preset), you don't need to take the installation steps described in this page.
However, it's still interesting knowing why they are needed.
:::


## Data objects

[Data](https://github.com/spatie/laravel-data) provides an interface to create rich data objects that can be used in various ways — for instance, it can replace [Form Requests](https://laravel.com/docs/9.x/validation#form-request-validation) or [resources](https://laravel.com/docs/9.x/eloquent-resources).

But most importantly, it allows us to generate TypeScript interfaces through the [TypeScript Transformer](https://github.com/spatie/laravel-typescript-transformer) package.

If you weren't using it before, you need to know that it's an essential part of building hybrid applications. You may install it using composer:

```bash
composer require spatie/laravel-data
```

## Transforming PHP to TypeScript

Thanks to Spatie, it's very easy to generate TypeScript interfaces from data objects and enums. Start by installing [TypeScript Transformer](https://github.com/spatie/laravel-typescript-transformer) and publish its configuration file:

```bash
composer require spatie/laravel-typescript-transformer
php artisan vendor:publish --tag=typescript-transformer-config
```

Open `config/typescript-transformer.php` and update the following properties:

```php
'collectors' => [
    Spatie\TypeScriptTransformer\Collectors\DefaultCollector::class, // [!vp --]
    Hybridly\Support\TypeScriptTransformer\DataResourceTypeScriptCollector::class, // [!vp ++]
    Spatie\LaravelData\Support\TypeScriptTransformer\DataTypeScriptCollector::class, // [!vp ++]
],
'transformers' => [
    Spatie\LaravelTypeScriptTransformer\Transformers\SpatieStateTransformer::class,  // [!vp --]
    Spatie\TypeScriptTransformer\Transformers\SpatieEnumTransformer::class, // [!vp --]
    Spatie\TypeScriptTransformer\Transformers\DtoTransformer::class, // [!vp --]
    Spatie\LaravelData\Support\TypeScriptTransformer\DataTypeScriptTransformer::class, // [!vp ++]
    Spatie\TypeScriptTransformer\Transformers\EnumTransformer::class, // [!vp ++]
],
```

The configuration above uses a collector provided by Hybridly that finds `DataResource` objects and generates typings with their authorizations. This is what powers [typed authorization support](./authorization.md).

It also configures the collector and transformer from `laravel-data` to transform data objects, as well as the `EnumTransformer` that is not, for some reason, configured by default.

:::info Transform anything
Note that you may add any collector and transformer that you want, and you may annotate any class with [`#[TypeScript]`](https://spatie.be/docs/typescript-transformer/v2/usage/annotations) for it to be transformed — provided it has a corresponding transformer.
:::

## Automating the conversion

With the setup above, it is still needed to manually run the `php artisan typescript:transform`. This can be automated through a Vite plugin.

Specifically, the `vite-plugin-run` plugin can run the transform command when a specific file changes. Start by installing it:

```shell
npm i -D vite-plugin-run
```

It then needs to be registered in `vite.config.ts`. The configuration generally depends on your project, but the following is a good start:

```ts
import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import hybridly from 'hybridly/vite'
import vue from '@vitejs/plugin-vue'
import run from 'vite-plugin-run' // [!vp focus]

export default defineConfig({
	plugins: [
		laravel({
			input: 'resources/application/main.ts',
			valetTls: true,
		}),
		run({ // [!vp focus:4]
			name: 'generate typescript',
			run: ['php', 'artisan', 'typescript:transform'],
			condition: (file) => ['Data.php', 'Enums'].some((kw) => file.includes(kw)),
		}),
		hybridly(),
		vue(),
	],
})
```
