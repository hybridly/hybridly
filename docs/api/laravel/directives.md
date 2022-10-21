# Directives

## `@hybridly`

This directive creates the markup required to initialize Vue with Hybridly and its initial data. Its arguments are optional, and, if provided, must use the named argument syntax.

### Usage

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
	<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			@vite('resources/application/main.ts')
	</head>
	<body class="bg-gray-50 antialiased">
			@hybridly(class: 'flex flex-col') // [!vp focus]
	</body>
</html>
```

### `id`

Sets the `id` of the generated HTML element. By default, `root` is used. Note that changing the `id` there requires changing it in [`initializeHybridly`](../utils/initialize-hybridly.md) as well.

```blade
@hybridly(id: 'app')
```

### `class`

Sets the `class` of the generated HTML element.

```blade
@hybridly(class: 'flex flex-col')
```

### `element`

Defines which element will get generated. By default, a  `div` is used.

```blade
@hybridly(element: 'main')
```
