<?php

namespace App;

use Discovery\Routing\Get;
use Discovery\Routing\Web;
use Illuminate\Support\Facades\Exceptions;
use RuntimeException;

final class Http500Controller
{
    #[Web, Get('/500')]
    public function __invoke(): never
    {
        Exceptions::fake();

        throw new RuntimeException('This is a simulated 500 error for demonstration purposes.');
    }
}
