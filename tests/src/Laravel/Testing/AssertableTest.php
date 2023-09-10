<?php

use Hybridly\Testing\Assertable;

test('the `view` method asserts that the view is the expected value', function () {
    make_hybrid_mock_request()->assertHybrid(function (Assertable $page) {
        $page->view('test');
    });
});

test('the `url` method asserts that the url is the expected value', function () {
    make_hybrid_mock_request(url: '/url-to-a-hybrid-page')->assertHybrid(function (Assertable $page) {
        $page->url(config('app.url') . '/url-to-a-hybrid-page');
    });
});

test('the `version` method asserts that the version is the expected value', function () {
    hybridly()->setVersion('owo');

    make_hybrid_mock_request()->assertHybrid(function (Assertable $page) {
        $page->version('owo');
    });
});

test('the `getPayload` method returns the payload', function () {
    make_hybrid_mock_request()->assertHybrid(function (Assertable $page) {
        expect($page->getPayload())->toBeArray();
        expect($page->getPayload())->toHaveKeys([
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
        ->assertHybrid(function (Assertable $page) {
            expect($page->getValue('view.component'))->toBe('test');
            expect($page->getValue('view.properties.foo'))->toBe('bar');
        });
});

test('the `getProperty` method returns the property value at the given path', function () {
    make_hybrid_mock_request(properties: ['foo' => 'bar'])
        ->assertHybrid(function (Assertable $page) {
            expect($page->getProperty('foo'))->toBe('bar');
        });
});

test('the `toArray` function converts the Assertable instance to an array', function () {
    make_hybrid_mock_request()->assertHybrid(function (Assertable $page) {
        expect($page->toArray())->toBeArray();
        expect($page->getPayload())->toHaveKeys([
            'view',
            'view.component',
            'view.properties',
            'dialog',
            'url',
            'version',
        ]);
    });
});
