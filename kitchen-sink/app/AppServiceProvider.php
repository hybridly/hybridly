<?php

namespace App;

use Hybridly\Hybridly;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpFoundation\Response;

use function Hybridly\view;

final class AppServiceProvider extends ServiceProvider
{
    public function boot(Hybridly $hybridly): void
    {
        $hybridly->renderExceptionsUsing(fn (Response $response) => view('error', [
            'status' => $response->getStatusCode(),
        ]));
    }
}
