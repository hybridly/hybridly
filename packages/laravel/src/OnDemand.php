<?php

namespace Hybridly;

use Illuminate\Support\Facades\App;

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
        return App::call($this->callback);
    }
}
