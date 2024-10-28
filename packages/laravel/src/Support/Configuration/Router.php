<?php

namespace Hybridly\Support\Configuration;

use Hybridly\Support\RouteExtractor;

final class Router
{
    public function __construct(
        public array $allowedVendors,
        public array $excludedRoutes,
        public string $routesExtractor,
    ) {
    }

    public static function fromArray(array $config): static
    {
        return new static(
            allowedVendors: $config['allowed_vendors'] ?? [
                'laravel/fortify',
            ],
            excludedRoutes: $config['exclude'] ?? [],
            routesExtractor: $config['routes_extractor'] ?? RouteExtractor::class,
        );
    }
}
