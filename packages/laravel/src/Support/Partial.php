<?php

namespace Hybridly\Support;

/**
 * Represents a property that will get evaluated only when specified in a partial reload.
 */
class Partial
{
    public function __construct(
        private readonly \Closure $callback,
    ) {
    }

    public static function make(\Closure $callback): static
    {
        return new static($callback);
    }

    public function __invoke(): mixed
    {
        return app()->call($this->callback);
    }
}
