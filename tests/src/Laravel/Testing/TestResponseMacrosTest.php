<?php

use Hybridly\Testing\Assertable;
use Illuminate\Testing\TestResponse;

it('can make hybrid assertions', function () {
    $response = makeMockRequest(hybridly('test'));

    $success = false;
    $response->assertHybrid(
        function (Assertable $page) use (&$success) {
            expect($page)->toBeInstanceOf(Assertable::class);
            $success = true;
        },
    );

    expect($success)->toBeTrue();
});

it('preserves the ability to continue chaining laravel test response calls', function () {
    $response = makeMockRequest(hybridly('test'));

    expect($response->assertHybrid())->toBeInstanceOf(TestResponse::class);
});

it('can retrieve the hybrid page', function () {
    $response = makeMockRequest(hybridly('test', ['bar' => 'baz']));

    tap($response->hybridPage(), function (array $page) {
        expect($page['view'])->toBe('test');
        expect($page['payload'])->toHaveKeys([
            'view',
            'view.name',
            'view.properties',
            'dialog',
            'url',
            'version',
        ]);
        expect($page['properties'])->toBe(['bar' => 'baz']);
        expect($page['dialog'])->toBeNull();
        expect($page['url'])->toEndWith('/example-url');
        expect($page['version'])->toBeNull();
    });
});
