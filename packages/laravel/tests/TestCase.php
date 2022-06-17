<?php

namespace Sleightful\Tests;

use Illuminate\Support\Facades\View;
use Orchestra\Testbench\TestCase as Orchestra;
use Sleightful\SleightfulServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            SleightfulServiceProvider::class,
        ];
    }
    
    protected function setUp(): void
    {
        parent::setUp();
        
        View::addLocation(__DIR__ . '/stubs');
    }
}
