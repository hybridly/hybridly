<?php

namespace Hybridly\Support\Data;

use Hybridly\Support\Properties\OnDemand;
use Spatie\LaravelData\Support\Lazy\ConditionalLazy;

use function Hybridly\on_demand;

class PartialLazy extends ConditionalLazy
{
    public function __construct(
        \Closure $closure,
    ) {
        parent::__construct(fn () => true, $closure);
    }

    public function resolve(): OnDemand
    {
        return on_demand($this->value);
    }
}
