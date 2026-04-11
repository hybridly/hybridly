<?php

use Hybridly\Tests\Fixtures\Database\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\artisan;

beforeEach(function () {
    File::cleanDirectory(base_path('.hybridly'));
});

test('route definitions can be generated', function () {
    Route::get('/', fn () => response())->name('index');

    artisan('hybridly:types')->assertOk();

    expect(File::exists(base_path('.hybridly/routes.d.ts')))->toBeTrue();
    expect(File::get(base_path('.hybridly/routes.d.ts')))
        ->toContain("declare module 'hybridly'")
        ->toContain('"index":')
        ->toContain('url:');
});

test('route model bindings are typed as any', function () {
    Route::get('/users/{user}', fn (User $user) => response($user->id))->name('users.show');

    artisan('hybridly:types')->assertOk();

    expect(File::get(base_path('.hybridly/routes.d.ts')))
        ->toContain('"users.show":')
        ->toContain('"bindings":{"user":any}');
});
