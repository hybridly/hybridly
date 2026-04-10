<?php

use Hybridly\HandleHybridRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(health: '/up')
    ->withExceptions()
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: HandleHybridRequests::class);
    })
    ->create();
