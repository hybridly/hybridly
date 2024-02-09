<?php

namespace Hybridly\Tests;

use Carbon\Carbon;
use Hybridly\HybridlyServiceProvider;
use Hybridly\Tests\Fixtures\Providers\TestingServiceProvider;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\View;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\LaravelData\LaravelDataServiceProvider;
use Spatie\LaravelRay\RayServiceProvider;

class TestCase extends Orchestra
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(now());
        View::addLocation(__DIR__ . '/stubs');
    }

    protected function getPackageProviders($app)
    {
        return [
            TestingServiceProvider::class,
            HybridlyServiceProvider::class,
            LaravelDataServiceProvider::class,
            RayServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/Fixtures/Database/migrations');
    }
}
