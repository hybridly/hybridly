<?php

namespace Hybridly\Tests;

use Hybridly\HybridlyServiceProvider;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\View;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\LaravelData\LaravelDataServiceProvider;
use Spatie\LaravelRay\RayServiceProvider;

class TestCase extends Orchestra
{
    use LazilyRefreshDatabase;

    protected function getPackageProviders($app)
    {
        return [
            HybridlyServiceProvider::class,
            LaravelDataServiceProvider::class,
            RayServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        View::addLocation(__DIR__ . '/stubs');
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/Fixtures/Database/migrations');
    }
}
