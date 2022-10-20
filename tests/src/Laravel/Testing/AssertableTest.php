<?php

use Hybridly\Testing\Assertable;

it('can assert the hybrid response view is the expected value', function () {
    $response = makeMockRequest(hybridly('test'));

    $response->assertHybrid(function (Assertable $page) {
        $page->view('test');
    });
});

it('can assert the hybrid response url is the expected value', function () {
    $response = makeMockRequest(hybridly('test'));

    $response->assertHybrid(function (Assertable $page) {
        $page->url(config('app.url') . '/example-url');
    });
});

it('can assert the hybrid response version is the expected value', function () {
    hybridly()->setVersion('owo');

    $response = makeMockRequest(hybridly('test'));

    $response->assertHybrid(function (Assertable $page) {
        $page->version('owo');
    });
});

test('Assertable can return the payload', function () {
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

test('Assertable can return prop value', function () {
    $response = makeMockRequest(hybridly('test'));

    $response->assertHybrid(function (Assertable $page) {
        expect($page->getValue('view.name'))->toBe('test');
    });
});

test('Assertable has toArray function', function () {
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
