<?php

namespace Hybridly\Support\Configuration;

final class Router
{
    public function __construct(
        public array $allowedVendors,
        public array $excludedRoutes,
    ) {
    }

    public static function fromArray(array $config): static
    {
        return new static(
            allowedVendors: $config['allowed_vendors'] ?? [
                'laravel/fortify',
            ],
            excludedRoutes: $config['exclude'] ?? [],
        );
    }
}
