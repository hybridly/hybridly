<?php

use Hybridly\Testing\Assertable;
use Illuminate\Testing\TestResponse;

it('can assert Hybrid responses', function () {
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

it('can assert non-Hybrid responses', function () {
    $response = makeMockRequest('hello-world');

    $success = false;
    $response->assertNotHybrid(
        function (Assertable $page) use (&$success) {
            expect($page)->toBeInstanceOf(Assertable::class);
            $success = true;
        },
    );

    expect($success)->toBeFalse();
});

it('can assert the given hybrid property exist', function () {
    $response = makeMockRequest(hybridly('test', ['foo' => 'bar']));

    $response->assertHasHybridProperty('foo');
});

it('can assert the given hybrid property is missing', function () {
    $response = makeMockRequest(hybridly('test', ['foo' => 'bar']));

    $response->assertMissingHybridProperty('owo');
});

it('can assert the property at the given path has the expected value', function () {
    $response = makeMockRequest(hybridly('test', ['foo' => 'bar']));

    $response->assertHybridProperty('foo', 'bar');
});

it('can assert the payload property at the given path has the expected value', function () {
    $response = makeMockRequest(hybridly('test', ['foo' => 'bar']));

    $response->assertHybridPayload('view.name', 'test');
    $response->assertHybridPayload('view.properties', ['foo' => 'bar']);
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
