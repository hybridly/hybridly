<?php

use Hybridly\Hybridly;

return [
    /*
    |--------------------------------------------------------------------------
    | Root view
    |--------------------------------------------------------------------------
    | By default, Hybridly expects to find a root.blade.php template in your
    | views folder. This option allows you to define an alternative root
    | template.
    |
    */
    'root_view' => Hybridly::DEFAULT_ROOT_VIEW,

    /*
    |--------------------------------------------------------------------------
    | Route filters
    |--------------------------------------------------------------------------
    | This option defines which routes Hybridly will expose to the frontend.
    |
    | By default, all vendor routes are hidden, but you may selectively expose
    | them by adding the vendor to the `allowed_vendors` array.
    |
    | Additionally, you may also exclude your own routes by adding them to the
    | `exclude` array. Filters in the `exclude` array support wildcards (*).
    |
    */
    'router' => [
        'allowed_vendors' => [
            'laravel/fortify',
        ],
        'exclude' => [
            // 'admin*'
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Force case
    |--------------------------------------------------------------------------
    | The case conventions between back-end and front-end is generally not
    | the same. To be able to stay consistent, Hybridly offers a way to
    | change the case of first-level properties shared to the front.
    |
    | Supported: null, 'snake', 'camel', 'kebab'
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
        'file_name_template' => '{locale}.json',
        'file_name' => 'locales.json',
    ],

    /*
    |--------------------------------------------------------------------------
    | Testing
    |--------------------------------------------------------------------------
    | The values described here are used to locate hybrid views on the
    | filesystem. For instance, when using `assertHybrid`, the assertion
    | attempts to locate the view as a file relative to any of the
    | paths AND with any of the extensions specified here.
    */
    'testing' => [
        'ensure_pages_exist' => true,
        'view_finder' => null,
        'page_paths' => [
            resource_path('pages'),
        ],
        'page_extensions' => [
            'ts',
            'tsx',
            'vue',
        ],
    ],
];
