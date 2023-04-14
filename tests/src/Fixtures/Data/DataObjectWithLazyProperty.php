<?php

namespace Hybridly\Tests\Fixtures\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;

class DataObjectWithLazyProperty extends Data
{
    public function __construct(
        public readonly bool $foo,
        public readonly Lazy|string $bar,
    ) {
    }
}
