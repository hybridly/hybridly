<?php

namespace Hybridly\Tests\Fixtures\Data;

use Spatie\LaravelData\Data;

final class NestedTransformedDateData extends Data
{
    public function __construct(
        public readonly TransformedDateData $child,
    ) {}
}
