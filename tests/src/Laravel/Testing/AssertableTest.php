<?php

use Hybridly\Testing\Assertable;

test('the `view` method asserts that the view is the expected value', function () {
    $response = makeMockRequest(response: hybridly('test'));

    $response->assertHybrid(function (Assertable $page) {
        $page->view('test');
    });
});

test('the `url` method asserts that the url is the expected value', function () {
    $response = makeMockRequest(response: hybridly('test'), url: '/url-to-a-hybrid-page');

    $response->assertHybrid(function (Assertable $page) {
        $page->url(config('app.url') . '/url-to-a-hybrid-page');
    });
});

test('the `version` method asserts that the version is the expected value', function () {
    hybridly()->setVersion('owo');

    $response = makeMockRequest(response: hybridly('test'));

    $response->assertHybrid(function (Assertable $page) {
        $page->version('owo');
    });
});

test('the `getPayload` method returns the payload', function () {
    $response = makeMockRequest(hybridly('test'));

    $response->assertHybrid(function (Assertable $page) {
        expect($page->getPayload())->toBeArray();
        expect($page->getPayload())->toHaveKeys([
            'view',
            'view.name',
            'view.properties',
            'dialog',
            'url',
            'version',
        ]);
    });
});

test('the `getValue` method returns the property value at the given path', function () {
    $response = makeMockRequest(response: hybridly('test', ['foo' => 'bar']));

    $response->assertHybrid(function (Assertable $page) {
        expect($page->getValue('view.name'))->toBe('test');
        expect($page->getValue('view.properties.foo'))->toBe('bar');
    });
});

test('the `toArray` function converts the Assertable instance to an array', function () {
    $response = makeMockRequest(hybridly('test'));

    $response->assertHybrid(function (Assertable $page) {
        expect($page->toArray())->toBeArray();
        expect($page->toArray())->toHaveKeys([
            'view',
            'payload',
            'properties',
            'dialog',
            'url',
            'version',
        ]);
    });
});
