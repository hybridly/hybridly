<?php

use Illuminate\Http\Request;
use Monolikit\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

function mockRequest(string $url = '/', string $method = 'GET', bool $bind = false, bool $monolikit = true)
{
    $request = Request::create($url, $method);

    if ($monolikit) {
        $request->headers->add(['X-Monolikit' => 'true']);
    }

    if ($bind) {
        app()->bind('request', fn () => $request);
    }

    return $request;
}
