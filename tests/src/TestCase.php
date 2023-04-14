<?php

namespace Hybridly\Tests;

use Hybridly\HybridlyServiceProvider;
use Illuminate\Support\Facades\View;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\LaravelData\LaravelDataServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            HybridlyServiceProvider::class,
            LaravelDataServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        View::addLocation(__DIR__ . '/stubs');
    }
}
