<?php

namespace Hybridly\Support\Configuration;

use Illuminate\Support\Arr;

/** @see https://hybridly.dev/guide/tables.html */
final class Tables
{
    public function __construct(
        public readonly bool $enableActions,
        public readonly string $actionsEndpoint,
        public readonly array $actionsEndpointMiddleware,
        public readonly string $actionsEndpointName,
    ) {
    }

    public static function fromArray(array $config): static
    {
        return new static(
            enableActions: $config['enable_actions'] ?? true,
            actionsEndpoint: $config['actions_endpoint'] ?? 'invoke',
            actionsEndpointMiddleware: Arr::wrap($config['actions_endpoint_middleware'] ?? []),
            actionsEndpointName: $config['actions_endpoint_name'] ?? 'hybridly.action.invoke',
        );
    }
}
