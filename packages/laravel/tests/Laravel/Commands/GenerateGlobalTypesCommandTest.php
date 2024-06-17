<?php

use Hybridly\Support\Configuration\Configuration;
use Hybridly\Tests\Laravel\Commands\Fixtures\CustomTransformer;
use Illuminate\Support\Facades\File;

use function Pest\Laravel\artisan;

beforeEach(function () {
    File::cleanDirectory(base_path('.hybridly'));
    File::cleanDirectory(base_path('app/Data'));
    File::cleanDirectory(base_path('app/Http/Middleware'));
});

function copy_stubs(array $paths): void
{
    foreach ($paths as $stub => $directory) {
        File::makeDirectory(base_path($directory), recursive: true, force: true);
        File::copy(
            path: __DIR__ . '/stubs/' . $stub,
            target: base_path($directory . '/' . $stub),
        );
    }
}

it('fails the command when there is no middleware, but still generates types', function () {
    copy_stubs([
        'UserData.php' => 'app/Data',
    ]);

    artisan('hybridly:types')
        ->assertExitCode(1)
        ->expectsOutputToContain('Could not find the Hybridly middleware in the application.');

    expect(File::exists(base_path('.hybridly/global-properties.d.ts')))->toBeTrue();
    expect(File::exists(base_path('.hybridly/php-types.d.ts')))->toBeTrue();
});

it('allows failures when `--allow-failures` is passed', function () {
    copy_stubs([
        'UserData.php' => 'app/Data',
    ]);

    artisan('hybridly:types --allow-failures')->assertExitCode(0);
});

it('succeeds when the middleware exists and generates types', function () {
    copy_stubs([
        'UserData.php' => 'app/Data',
        'SharedData.php' => 'app/Data',
        'HandleHybridRequests.php' => 'app/Http/Middleware',
    ]);

    artisan('hybridly:types')->assertExitCode(0);

    expect(File::exists(base_path('.hybridly/php-types.d.ts')))->toBeTrue();
    expect(File::exists(base_path('.hybridly/global-properties.d.ts')))->toBeTrue();
})->skip('Does not work, probably due to where the Laravel skeleton is');

it('generates php types', function () {
    copy_stubs([
        'UserData.php' => 'app/Data',
        'SharedData.php' => 'app/Data',
    ]);

    artisan('hybridly:types')->assertExitCode(1);

    expect(File::exists(base_path('.hybridly/php-types.d.ts')))->toBeTrue();
    expect(File::get(base_path('.hybridly/php-types.d.ts')))
        ->toContain('UserData')
        ->toContain('SharedData');
})->skip('Does not work, probably due to where the Laravel skeleton is');

it('accepts custom transformers', function () {
    Configuration::get()->typescript->namespaceTransformer = CustomTransformer::class;

    copy_stubs([
        'UserData.php' => 'app/Data',
    ]);

    artisan('hybridly:types');

    expect(File::exists(base_path('.hybridly/php-types.d.ts')))->toBeTrue();
    expect(File::get(base_path('.hybridly/php-types.d.ts')))
        ->toContain('Data.UserData')
        ->not->toContain('App.Data.UserData');
})->skip('Does not work, probably due to where the Laravel skeleton is');
