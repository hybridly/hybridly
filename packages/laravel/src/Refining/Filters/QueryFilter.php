<?php

namespace Hybridly\Refining\Filters;

use Hybridly\Refining\Filters\Operator;

final class QueryFilter
{
    public function __construct(
        public readonly null|int|string|array $value,
        public readonly ?string $search = null,
        public readonly ?Operator $operator = null,
        public readonly array $options = [],
    ) {}
}
