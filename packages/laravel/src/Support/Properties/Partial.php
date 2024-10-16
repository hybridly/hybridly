<?php

namespace Hybridly\Support\Properties;

/**
 * Represents a property that will get evaluated only when specified in a partial reload.
 */
class Partial implements Property, IgnoreFirstLoad
{
    public function __construct(
        private \Closure $callback,
    ) {
    }

    public function __invoke(): mixed
    {
        return app()->call($this->callback);
    }
}
