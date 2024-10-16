<?php

namespace Hybridly\Support\Properties;

final class Merge implements Property, Mergeable
{
    use MergesProperties;

    public function __construct(
        private mixed $value,
        private bool $merge = true,
        private bool $unique = false,
    ) {
    }

    public function __invoke(): mixed
    {
        return \is_callable($this->value)
            ? app()->call($this->value)
            : $this->value;
    }
}
