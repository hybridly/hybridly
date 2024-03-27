<?php

use function Pest\Laravel\artisan;

test('configuration can be printed', function () {
    artisan('hybridly:config')->assertOk();
});

test('configuration subsets can be pretty-printed', function (string $path, string $output) {
    artisan("hybridly:config --pretty {$path}")
        ->assertOk()
        ->expectsOutput($output);
})->with([
    'architecture' => ['architecture', <<<JSON
    {
        "root_directory": "resources",
        "components_directory": "components",
        "application_main_path": "resources/application/main.ts"
    }
    JSON],
    'architecture.root_directory' => ['architecture.root_directory', <<<JSON
    "resources"
    JSON],
]);
