<?php

namespace Hybridly\Support\Properties;

/**
 * Represents a property that will always be loaded.
 */
final class Persistent implements Property, Mergeable
{
    use MergesProperties;

    public function __construct(
        private \Closure $callback,
        private bool $merge = false,
        private bool $unique = false,
        private ?string $group = null,
    ) {
    }

    public function __invoke(): mixed
    {
        return app()->call($this->callback);
    }

    public function group(): string
    {
        return $this->group;
    }
}
