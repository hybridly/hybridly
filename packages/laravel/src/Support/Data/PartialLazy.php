<?php

namespace Hybridly\Support\Data;

use Hybridly\Support\Partial;
use Spatie\LaravelData\Support\Lazy\ConditionalLazy;

class PartialLazy extends ConditionalLazy
{
    public function __construct(
        \Closure $closure,
    ) {
        parent::__construct(fn () => true, $closure);
    }

    public function resolve(): Partial
    {
        return hybridly()->partial($this->value);
    }
}
