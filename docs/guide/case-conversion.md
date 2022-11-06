# Case conversion

## Overview

PHP and TypeScript have their own convention when it comes to the code style, specifically regarding objects and arrays. 

While it's common to have `snake_case` array keys in PHP, TypeScript objects are generally written using `camelCase`. Vue's style guide [also recommends using `camelCase` when defining properties](https://eslint.vuejs.org/rules/prop-name-casing.html) in single-file components.

Hybridly provides a way to keep using `snake_case` when hybrid properties and receiving them in `camelCase` in single-file components.

## Enabling case conversion

By default, in order to avoid any confusion, this feature is disabled. It may be configured in `config/hybridly.php`.

```php
return [
    'force_case' => [
        'input' => 'snake',
        'output' => 'camel',
    ],
];
```

The `output` option converts properties that are sent to single-file components. In the example above, properties can be sent to single-file components using any case, and single-file components will receive them as `camelCase`.

The `input` option converts properties that are sent to Laravel, specifically partial properties. In the example above, partial properties can be referred to using any case, as long as they are using `snake_case` in PHP code.

:::info Top-level properties only
Note that this feature only applies to top-level properties. While it could be implemented for nested properties as well, this could create a lot of confusion and would interfere with generated TypeScript definitions.
:::
