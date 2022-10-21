<?php

use Hybridly\Testing\Assertable;
use Illuminate\Testing\TestResponse;

test('the `assertHybrid` method runs its callback', function () {
    $response = makeMockRequest(response: hybridly('test'));
    $success = false;

    $response->assertHybrid(function (Assertable $page) use (&$success) {
        expect($page)->toBeInstanceOf(Assertable::class);
        $success = true;
    });

    expect($success)->toBeTrue();
});

test('the `assertNotHybrid` method asserts that non-hybrid responses are non-hybrid', function () {
    makeMockRequest(response: 'hello-world')->assertNotHybrid();
    $success = false;

    try {
        makeMockRequest(response: hybridly('test'))->assertNotHybrid();
    } catch (\PHPUnit\Framework\AssertionFailedError) {
        $success = true;
    }

    expect($success)->toBe(true);
});

test('the `assertHasHybridProperty` method asserts the given property exists', function () {
    $response = makeMockRequest(response: hybridly('test', [
        'foo' => 'bar',
        'nested' => [
            'foo' => 'bar',
        ],
    ]));

    $response->assertHasHybridProperty('foo');
    $response->assertHasHybridProperty('nested.foo');
});

test('the `assertMissingHybridProperty` method asserts the given property is missing', function () {
    makeMockRequest(response: hybridly('test', [
        'foo' => 'bar',
    ]))->assertMissingHybridProperty('owo');
});

test('the `assertHybridProperty` method asserts the property at the given path has the expected value', function () {
    makeMockRequest(response: hybridly('test', [
        'foo' => 'bar',
    ]))->assertHybridProperty('foo', 'bar');
});

test('the `assertHybridPayload` method asserts the payload property at the given path has the expected value', function () {
    $response = makeMockRequest(response: hybridly('test', ['foo' => 'bar']));

    $response->assertHybridPayload('view.name', 'test');
    $response->assertHybridPayload('view.properties', ['foo' => 'bar']);
});

test('the `assertHybridView` method asserts the hybrid response view is the expected value', function () {
    makeMockRequest(response: hybridly('some.dotted.view'))
        ->assertHybridView('some.dotted.view');
});

test('the `assertHybridVersion` method asserts the hybrid response version is the expected value', function () {
    hybridly()->setVersion('owo');

    makeMockRequest(response: hybridly('test'))
        ->assertHybridVersion('owo');
});

test('the `assertHybridUrl` method asserts the hybrid response url is the expected value', function () {
    makeMockRequest(response: hybridly('test'))
        ->assertHybridUrl(config('app.url') . '/example-url');
});

test('the `assertHybrid` method returns a `TestResponse` instance', function () {
    $response = makeMockRequest(response: hybridly('test'));

    expect($response->assertHybrid())->toBeInstanceOf(TestResponse::class);
});

test('the `getHybridPayload` method returns the payload of the hybrid response', function () {
    $response = makeMockRequest(response: hybridly('test', ['bar' => 'baz']));

    tap($response->getHybridPayload(), function (array $page) {
        expect($page['view']['name'])->toBe('test');
        expect($page['view']['properties'])->toBe(['bar' => 'baz']);
        expect($page['dialog'])->toBeNull();
        expect($page['url'])->toBe(config('app.url') . '/example-url');
        expect($page['version'])->toBeNull();
    });
});
