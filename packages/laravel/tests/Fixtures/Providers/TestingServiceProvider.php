<?php

namespace Hybridly\Tests\Fixtures\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

final class TestingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::define('returns-true', fn () => true);
        Gate::define('returns-false', fn () => false);
    }
}
