<?php

use Hybridly\Support\RouteExtractor;
use Illuminate\Broadcasting\BroadcastController;
use Illuminate\Support\Facades\Route;
use Pest\Expectation;

beforeEach(function () {
    app()->setBasePath(str_replace('/vendor/orchestra/testbench-core/laravel', '', app()->basePath()));
});

test('only named routes are extracted', function () {
    Route::get('/not-named', fn () => response());
    Route::get('/named', fn () => response())->name('named');

    expect(app(RouteExtractor::class)->getRoutes())
        ->toHaveCount(1)
        ->sequence(
            fn (Expectation $route) => $route->toBe([
                'domain' => null,
                'method' => ['GET', 'HEAD'],
                'uri' => 'named',
                'name' => 'named',
                'bindings' => [],
                'wheres' => [],
            ]),
        );
});

test('routes can be filtered out', function () {
    Route::get('/filtered-route', fn () => response())->name('filtered-route');
    Route::get('/ok', fn () => response())->name('ok');

    config(['hybridly.router.exclude' => 'filtered*']);

    expect(app(RouteExtractor::class)->getRoutes())
        ->toHaveCount(1)
        ->sequence(fn ($_, $key) => $key->toBe('ok'));
});

test('routes from vendors are excluded by default', function () {
    Route::get('/vendor-route', [BroadcastController::class, 'authenticate'])->name('vendor-route');

    expect(app(RouteExtractor::class)->getRoutes())->toHaveCount(0);
});

test('routes from vendors can be opted-in', function () {
    Route::get('/vendor-route', [BroadcastController::class, 'authenticate'])->name('vendor-route');

    config(['hybridly.router.allowed_vendors' => ['laravel/framework']]);

    expect(app(RouteExtractor::class)->getRoutes())
        ->toHaveCount(1)
        ->sequence(fn ($_, $key) => $key->toBe('vendor-route'));
});
