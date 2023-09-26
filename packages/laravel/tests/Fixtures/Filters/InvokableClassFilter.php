<?php

namespace Hybridly\Tests\Fixtures\Filters;

use Illuminate\Contracts\Database\Eloquent\Builder;

class InvokableClassFilter
{
    public function __invoke(Builder $builder, mixed $value, string $property): void
    {
        $builder->where($property, '=', $value);
    }
}
