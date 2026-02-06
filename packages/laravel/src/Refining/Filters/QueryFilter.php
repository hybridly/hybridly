<?php

namespace Hybridly\Refining\Filters;

final class QueryFilter
{
    public function __construct(
        public readonly null|int|string|array $value,
        public readonly ?string $search = null,
    ) {}
}
