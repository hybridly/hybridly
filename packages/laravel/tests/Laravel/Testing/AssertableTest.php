<?php

use Hybridly\Support\Header;
use Hybridly\Testing\Assertable;

use function Hybridly\properties;

test('`fromTestResponse` works with properties-only responses', function () {
    make_mock_request(
        response: properties(['foo' => 'bar']),
        headers: [
            Header::HYBRID_REQUEST => true,
        ],
    )->assertHybrid();
});

test('the `assertHybridViewComponent` method asserts that the view is the expected value', function () {
    make_hybrid_mock_request()->assertHybrid(function (Assertable $view) {
        $view->assertViewComponent('test');
    });
});

test('the `assertViewUrl` method asserts that the url is the expected value', function () {
    make_hybrid_mock_request(url: '/url-to-a-page')->assertHybrid(function (Assertable $view) {
        $view->assertViewUrl(config('app.url') . '/url-to-a-page');
    });
});

test('the `assertHybridVersion` method asserts that the version is the expected value', function () {
    hybridly()->setVersion('owo');

    make_hybrid_mock_request()->assertHybrid(function (Assertable $view) {
        $view->assertHybridVersion('owo');
    });
});

test('the `getPayload` method returns the payload', function () {
    make_hybrid_mock_request()->assertHybrid(function (Assertable $view) {
        expect($view->getPayload())->toBeArray();
        expect($view->getPayload())->toHaveKeys([
            'view',
            'view.component',
            'view.properties',
            'dialog',
            'url',
            'version',
        ]);
    });
});

test('the `getValue` method returns the value at the given path', function () {
    make_hybrid_mock_request(properties: ['foo' => 'bar'])
        ->assertHybrid(function (Assertable $view) {
            expect($view->getValue('view.component'))->toBe('test');
            expect($view->getValue('view.properties.foo'))->toBe('bar');
        });
});

test('the `getProperty` method returns the property value at the given path', function () {
    make_hybrid_mock_request(properties: ['foo' => 'bar'])
        ->assertHybrid(function (Assertable $view) {
            expect($view->getProperty('foo'))->toBe('bar');
        });
});

test('the `toArray` function converts the Assertable instance to an array', function () {
    make_hybrid_mock_request()->assertHybrid(function (Assertable $view) {
        expect($view->toArray())->toBeArray();
        expect($view->getPayload())->toHaveKeys([
            'view',
            'view.component',
            'view.properties',
            'dialog',
            'url',
            'version',
        ]);
    });
});
