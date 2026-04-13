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

it('allows failures when `--allow-failures` is passed', function () {
    copy_stubs([
        'UserData.php' => 'app/Data',
    ]);

    artisan('hybridly:types --allow-failures')->assertExitCode(0);
});

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
