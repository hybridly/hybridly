<?php

namespace Monolikit;

/**
 * Represents a property that will get evaluated only when specifically required.
 */
class Lazy
{
    public function __construct(
        protected \Closure $callback,
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
