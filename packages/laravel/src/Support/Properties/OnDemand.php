<?php

namespace Hybridly\Support\Properties;

/**
 * Represents a property that will get evaluated only when specified.
 */
readonly class OnDemand implements Property, IgnoreFirstLoad
{
    public function __construct(
        private \Closure $callback,
    ) {}

    public function evaluate(): mixed
    {
        return app()->call($this->callback);
    }
}
