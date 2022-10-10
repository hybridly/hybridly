<?php

use Illuminate\Http\Request;
use Hybridly\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

function mockRequest(string $url = '/', string $method = 'GET', bool $bind = false, bool $hybridly = true)
{
    $request = Request::create($url, $method);

    if ($hybridly) {
        $request->headers->add(['X-Hybridly' => 'true']);
    }

    if ($bind) {
        app()->bind('request', fn () => $request);
    }

    return $request;
}
