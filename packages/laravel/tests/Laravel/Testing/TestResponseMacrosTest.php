<?php

use Hybridly\Testing\Assertable;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;

use function Pest\Laravel\get;

test('the `assertHybrid` method runs its callback', function () {
    $success = false;

    make_hybrid_mock_request()->assertHybrid(function (Assertable $view) use (&$success) {
        expect($view)->toBeInstanceOf(Assertable::class);
        $success = true;
    });

    expect($success)->toBeTrue();
});

test('the `assertHybridDialog` method asserts dialog view component & base url & properties', function () {
    Route::get('/test/view', fn () => hybridly('test.view'))->name('test.view');
    Route::get('/test/dialog', fn () => hybridly('test.dialog', ['foo' => 'bar'])->base('test.view'))->name('test.dialog');

    get('/test/dialog')
        ->assertHybridView('test.view')
        ->assertHybridUrl('http://localhost/test/dialog')
        ->assertHybridDialog(
            baseUrl: 'http://localhost/test/view',
            view: 'test.dialog',
            properties: [
                'foo' => 'bar',
            ],
        );
});

test('the `assertNotHybrid` method asserts that non-hybrid responses are non-hybrid', function () {
    make_mock_request(response: 'hello-world')->assertNotHybrid();
    $success = false;

    try {
        make_hybrid_mock_request()->assertNotHybrid();
    } catch (\PHPUnit\Framework\AssertionFailedError) {
        $success = true;
    }

    expect($success)->toBe(true);
});

test('the `assertHasHybridProperty` method asserts the given property exists', function () {
    $response = make_hybrid_mock_request(properties: [
        'foo' => 'bar',
        'nested' => [
            'foo' => 'bar',
        ],
    ]);

    $response->assertHasHybridProperty('foo');
    $response->assertHasHybridProperty('nested.foo');
});

test('the `assertMissingHybridProperty` method asserts the given property is missing', function () {
    make_hybrid_mock_request(properties: ['foo' => 'bar'])
        ->assertMissingHybridProperty('owo');
});

test('the `assertHybridProperty` method asserts the property at the given path has the expected value', function () {
    make_hybrid_mock_request(properties: ['foo' => 'bar'])
        ->assertHybridProperty('foo', 'bar');
});

test('the `assertHybridProperties` method asserts the properties using the given array', function () {
    make_hybrid_mock_request(properties: [
        'case1' => 'abc',
        'case2' => 'bar',
        'case3' => 'zyx',
        'case4' => [1, 2, 3],
        'case5' => 5,
        'case6' => [
            'hello' => 'world',
            'say' => 'hi',
        ],
        'case7' => [
            'drink' => 'water',
            'stay' => 'hydrated',
        ],
        'case8' => null,
        'case9' => true,
        'case10' => false,
        'case11' => ['hey'],
    ])->assertHybridProperties([
        'case1', // asserts it exists
        'case2' => 'bar', // asserts it has the given value
        'case3' => fn ($case) => expect($case)->toBe('zyx'), // asserts using callback
        'case4' => 3, // asserts it has the given count
        'case5' => 5, // asserts it has the given value
        'case6' => [
            'hello' => 'world',
            'say' => 'hi',
        ],
        'case6.hello' => 'world', // asserts it has the given value
        'case6.say' => 'hi', // asserts it has the given value
        'case6.say' => fn ($say) => expect($say)->toBe('hi'), // asserts using callback and dot notation
        'case7' => fn (Assertable $assert) => $assert->hasAll(['drink', 'stay']), // asserts using callback and typehinted parameter
        'case8' => null, // assert that value is null
        'case9' => true, // assert that value is true
        'case10' => false, // assert that value is false
        'case11' => ['hey'],
    ]);
});

test('the `assertHybridPayload` method asserts the payload property at the given path has the expected value', function () {
    make_hybrid_mock_request(properties: ['foo' => 'bar'])
        ->assertHybridPayload('view.component', 'test')
        ->assertHybridPayload('view.properties', ['foo' => 'bar']);
});

test('the `assertHybridView` method asserts the hybrid response view is the expected value', function () {
    make_hybrid_mock_request(component: 'some.dotted.view')
        ->assertHybridView('some.dotted.view');
});

test('the `assertHybridVersion` method asserts the hybrid response version is the expected value', function () {
    hybridly()->setVersion('owo');
    make_hybrid_mock_request()->assertHybridVersion('owo');
});

test('the `assertHybridUrl` method asserts the hybrid response url is the expected value', function () {
    make_hybrid_mock_request()
        ->assertHybridUrl(config('app.url') . '/hybrid-mock-url');
});

test('the `assertHybrid` method returns a `TestResponse` instance', function () {
    expect(make_hybrid_mock_request()->assertHybrid())
        ->toBeInstanceOf(TestResponse::class);
});

test('the `getHybridPayload` method returns the payload of the hybrid response', function () {
    $response = make_hybrid_mock_request(properties: ['bar' => 'baz']);

    tap($response->getHybridPayload(), function (array $view) {
        expect($view['view']['component'])->toBe('test');
        expect($view['view']['properties'])->toBe(['bar' => 'baz']);
        expect($view['dialog'])->toBeNull();
        expect($view['url'])->toBe(config('app.url') . '/hybrid-mock-url');
        expect($view['version'])->toBeNull();
    });
});

test('the `getHybridProperty` method returns the given property', function () {
    $response = make_hybrid_mock_request(properties: [
        'foo' => 'bar',
        'uwu' => [
            'owo' => 'hewwo',
        ],
    ]);

    expect($response->getHybridProperty('foo'))->toBe('bar');
    expect($response->getHybridProperty('uwu.owo'))->toBe('hewwo');
});
