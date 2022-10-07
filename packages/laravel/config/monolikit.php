<?php

use Monolikit\Monolikit;

return [
    /*
    |--------------------------------------------------------------------------
    | Default root view
    |--------------------------------------------------------------------------
    */
    'root_view' => Monolikit::DEFAULT_ROOT_VIEW,

    /*
    |--------------------------------------------------------------------------
    | Force case
    |--------------------------------------------------------------------------
    | The case conventions between back-end and front-end is generally not
    | the same. To be able to stay consistent, Monolikit offers a way to
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
    'i18n' => [
        'lang_path' => base_path('lang'),
        'write_path' => resource_path('scripts/i18n.json'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Testing
    |--------------------------------------------------------------------------
    |
    | The values described here are used to locate Monolikit views on the
    | filesystem. For instance, when using `assertMonolikit`, the assertion
    | attempts to locate the view as a file relative to any of the
    | paths AND with any of the extensions specified here.
    |
    */

    'testing' => [

        'ensure_pages_exist' => true,

        'page_paths' => [

            resource_path('views/pages'),

        ],

        'page_extensions' => [

            'js',
            'jsx', // Is this needed ?
            'ts',
            'tsx', // Is this needed ?
            'vue',

        ],

    ],
];
