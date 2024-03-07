<?php

namespace Hybridly\Support\Data;

use Hybridly\Support\Partial;
use Spatie\LaravelData\Support\Lazy\ConditionalLazy;

use function Hybridly\partial;

class PartialLazy extends ConditionalLazy
{
    public function __construct(
        \Closure $closure,
    ) {
        parent::__construct(fn () => true, $closure);
    }

    public function resolve(): Partial
    {
        return partial($this->value);
    }
}
