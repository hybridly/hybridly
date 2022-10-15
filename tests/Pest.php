<?php

use Hybridly\Hybridly;
use Hybridly\Tests\TestCase;
use Illuminate\Http\Request;

uses(TestCase::class)->in(__DIR__);

function mockRequest(string $url = '/', string $method = 'GET', bool $bind = false, bool $hybridly = true)
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
