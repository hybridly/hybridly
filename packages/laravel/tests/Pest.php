<?php

use Illuminate\Http\Request;
use Sleightful\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

function mockRequest(string $url = '/', string $method = 'GET', bool $bind = false, bool $sleightful = true)
{
    $request = Request::create($url, $method);

    if ($sleightful) {
        $request->headers->add(['X-Sleightful' => 'true']);
    }
    
    if ($bind) {
        app()->bind('request', fn () => $request);
    }

    return $request;
}
