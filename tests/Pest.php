<?php

use Hybridly\Hybridly;
use Hybridly\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Testing\TestResponse;

use function Pest\Laravel\get;

uses(TestCase::class)->beforeEach(function () {
    config()->set('hybridly.testing.ensure_pages_exist', false);
})->in(__DIR__);

function mockRequest(string $url = '/', string $method = 'GET', bool $bind = false, bool $hybridly = true): Request
{
    $request = Request::create($url, $method);

    if ($hybridly) {
        $request->headers->add([Hybridly::HYBRIDLY_HEADER => 'true']);
    }

    if ($bind) {
        app()->bind('request', fn () => $request);
    }

    return $request;
}

function makeMockRequest($view): TestResponse
{
    app('router')->get('/example-url', function () use ($view) {
        return $view;
    });

    return get('/example-url');
}
