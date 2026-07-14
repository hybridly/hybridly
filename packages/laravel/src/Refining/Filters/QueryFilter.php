<?php

namespace Hybridly\Refining\Filters;

final class QueryFilter
{
    public function __construct(
        public readonly int|float|string|bool|array|null $value,
        public readonly ?string $search = null,
        public readonly ?Operator $operator = null,
        public readonly array $options = [],
        public readonly ?string $suggestionKey = null,
    ) {}
}
