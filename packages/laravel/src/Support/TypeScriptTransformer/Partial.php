<?php

namespace Hybridly\Support\Properties;

/**
 * Represents a property that will get evaluated only when specified in a partial reload.
 */
class Partial implements IgnoreFirstLoad
{
    public function __construct(
        private readonly \Closure $callback,
    ) {
    }

    public function __invoke(): mixed
    {
        return app()->call($this->callback);
    }

    public static function make(\Closure $callback): static
    {
        return new static($callback);
    }
}
