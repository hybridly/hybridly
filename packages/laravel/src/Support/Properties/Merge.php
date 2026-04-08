<?php

namespace Hybridly\Support\Properties;

/**
 * Represents a property that should merge itself with its current instance.
 */
final class Merge implements Property, Mergeable
{
    use MergesProperties;

    public function __construct(
        private mixed $value,
        private bool $merge = true,
        private bool $unique = false,
    ) {}

    public function evaluate(): mixed
    {
        return \is_callable($this->value)
            ? app()->call($this->value)
            : $this->value;
    }
}
