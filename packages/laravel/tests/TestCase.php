<?php

namespace Monolikit\Tests;

use Illuminate\Support\Facades\View;
use Monolikit\MonolikitServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            MonolikitServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        View::addLocation(__DIR__ . '/stubs');
    }
}
