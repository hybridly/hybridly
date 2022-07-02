<?php

use Hybridly\Hybridly;

return [
    /*
    |--------------------------------------------------------------------------
    | Default root view
    |--------------------------------------------------------------------------
    */
    'root_view' => Hybridly::DEFAULT_ROOT_VIEW,

    /*
    |--------------------------------------------------------------------------
    | Force case
    |--------------------------------------------------------------------------
    | The convention for array properties in PHP is usually `snake_case`.
    | The convention for component properties in Vue is `camelCase`.
    | For this reason, you can force the case used for properties.
    |
    | Supported: null, 'snake', 'camel'
    */
    'force_case' => [
        'input' => null,
        'output' => null,
    ],
];
