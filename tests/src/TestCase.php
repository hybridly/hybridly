<?php

namespace Hybridly\Tests;

use Illuminate\Support\Facades\View;
use Hybridly\HybridlyServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            HybridlyServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        View::addLocation(__DIR__ . '/stubs');
    }
}
