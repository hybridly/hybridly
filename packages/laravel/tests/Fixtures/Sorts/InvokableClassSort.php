<?php

namespace Hybridly\Tests\Fixtures\Sorts;

use Illuminate\Contracts\Database\Eloquent\Builder;

class InvokableClassSort
{
    public function __invoke(Builder $builder, string $direction, string $property): void
    {
        $builder->orderBy(
            column: $builder->qualifyColumn($property),
            direction: $direction,
        );
    }
}
