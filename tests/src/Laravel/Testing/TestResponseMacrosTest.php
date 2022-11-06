<?php

use Hybridly\Testing\Assertable;
use Illuminate\Testing\TestResponse;

test('the `assertHybrid` method runs its callback', function () {
    $success = false;

    makeHybridMockRequest()->assertHybrid(function (Assertable $page) use (&$success) {
        expect($page)->toBeInstanceOf(Assertable::class);
        $success = true;
    });

    expect($success)->toBeTrue();
});

test('the `assertNotHybrid` method asserts that non-hybrid responses are non-hybrid', function () {
    makeMockRequest(response: 'hello-world')->assertNotHybrid();
    $success = false;

    try {
        makeHybridMockRequest()->assertNotHybrid();
    } catch (\PHPUnit\Framework\AssertionFailedError) {
        $success = true;
    }

    expect($success)->toBe(true);
});

test('the `assertHasHybridProperty` method asserts the given property exists', function () {
    $response = makeHybridMockRequest(properties: [
        'foo' => 'bar',
        'nested' => [
            'foo' => 'bar',
        ],
    ]);

    $response->assertHasHybridProperty('foo');
    $response->assertHasHybridProperty('nested.foo');
});

test('the `assertMissingHybridProperty` method asserts the given property is missing', function () {
    makeHybridMockRequest(properties: ['foo' => 'bar'])
        ->assertMissingHybridProperty('owo');
});

test('the `assertHybridProperty` method asserts the property at the given path has the expected value', function () {
    makeHybridMockRequest(properties: ['foo' => 'bar'])
        ->assertHybridProperty('foo', 'bar');
});

test('the `assertHybridProperties` method asserts the properties using the given array', function () {
    makeHybridMockRequest(properties: [
        'foo' => 'bar',
        'baz' => [1, 2, 3],
        'uwu' => [
            'owo' => 'hewwo',
            'ewe' => 'world',
        ],
    ])->assertHybridProperties([
        'foo', // asserts it exists
        'foo' => 'bar', // asserts it has the given value
        'uwu' => ['owo', 'ewe'],
        'uwu.owo' => 'hewwo', // asserts it has the given value
        'uwu.ewe' => 'world', // asserts it has the given value
        'baz' => 3, // asserts it has the given count
        'uwu' => fn (Assertable $uwu) => $uwu->hasAll(['owo', 'ewe']), // asserts using callback
    ]);
});

test('the `assertHybridPayload` method asserts the payload property at the given path has the expected value', function () {
    makeHybridMockRequest(properties: ['foo' => 'bar'])
        ->assertHybridPayload('view.name', 'test')
        ->assertHybridPayload('view.properties', ['foo' => 'bar']);
});

test('the `assertHybridView` method asserts the hybrid response view is the expected value', function () {
    makeHybridMockRequest(component: 'some.dotted.view')
        ->assertHybridView('some.dotted.view');
});

test('the `assertHybridVersion` method asserts the hybrid response version is the expected value', function () {
    hybridly()->setVersion('owo');
    makeHybridMockRequest()->assertHybridVersion('owo');
});

test('the `assertHybridUrl` method asserts the hybrid response url is the expected value', function () {
    makeHybridMockRequest()
        ->assertHybridUrl(config('app.url') . '/hybrid-mock-url');
});

test('the `assertHybrid` method returns a `TestResponse` instance', function () {
    expect(makeHybridMockRequest()->assertHybrid())
        ->toBeInstanceOf(TestResponse::class);
});

test('the `getHybridPayload` method returns the payload of the hybrid response', function () {
    $response = makeHybridMockRequest(properties: ['bar' => 'baz']);

    tap($response->getHybridPayload(), function (array $page) {
        expect($page['view']['name'])->toBe('test');
        expect($page['view']['properties'])->toBe(['bar' => 'baz']);
        expect($page['dialog'])->toBeNull();
        expect($page['url'])->toBe(config('app.url') . '/hybrid-mock-url');
        expect($page['version'])->toBeNull();
    });
});
