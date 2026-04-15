<?php

use function Pest\Laravel\artisan;

test('configuration can be printed', function () {
    artisan('hybridly:config')->assertOk();
});

test('configuration subsets can be pretty-printed', function () {
    artisan('hybridly:config --pretty architecture')
        ->assertOk()
        ->expectsOutputToContain('"root_directory": ');
});
