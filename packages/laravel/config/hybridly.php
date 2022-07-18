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
    | The case conventions between back-end and front-end is generally not
    | the same. To be able to stay consistent, Hybridly offers a way to
    | change the case of first-level properties shared to the front.
    |
    | Supported: null, 'snake', 'camel'
    */
    'force_case' => [
        'input' => null,
        'output' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | i18n
    |--------------------------------------------------------------------------
    | You can chose where the generated internationalization JSON file
    | will be written to using this option. To generate that file,
    | you may use the `i18n:generate` artisan command.
    */
    'i18n_path' => resource_path('scripts/i18n.json'),
];
