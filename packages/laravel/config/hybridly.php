<?php

use Hybridly\Hybridly;

return [
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
    | Architecture
    |--------------------------------------------------------------------------
    | Hybridly has presets for a default, one-level architecture, as well
    | as a module-based architecture. Optionally, you may disable the
    | presets altogether and define your own architecture.
    |
    | See: https://hybridly.dev/guide/architecture.html
    */
    'architecture' => [
        'preset' => 'default',
        'root' => 'resources',
        'application' => 'resources/application/main.ts',
        'eager_load_views' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Refining
    |--------------------------------------------------------------------------
    | When refininig queries, the `sorts_key` and `filters_key` options
    | define the names of the query parameters that will be generated
    | using the provided front-end utilities.
    |
    | See: https://hybridly.dev/guide/refining.html
    */
    'refining' => [
        'sorts_key' => 'sort',
        'filters_key' => 'filters',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tables
    |--------------------------------------------------------------------------
    | The `tables` option defines the endpoint that will be used to
    | invoke table actions, as well as the name of the route that
    | will be used to generate the endpoint URL.
    |
    | See: https://hybridly.dev/guide/tables.html
    */
    'tables' => [
        'enable_actions' => true,
        'actions_endpoint' => 'invoke',
        'actions_endpoint_name' => 'hybridly.action.invoke',
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
    | Root view
    |--------------------------------------------------------------------------
    | By default, Hybridly expects to find a `root.blade.php` template in
    | `resources/application`. This option allows you to define an
    | alternative root template.
    */
    'root_view' => Hybridly::DEFAULT_ROOT_VIEW,

    /*
    |--------------------------------------------------------------------------
    | Testing
    |--------------------------------------------------------------------------
    | When `ensure_views_exist` is enabled, Hybridly will ensure that views
    | actually exist on the disk when hybrid testing utilities are used.
    */
    'testing' => [
        'ensure_views_exist' => true,
    ],
];
