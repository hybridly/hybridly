<?php

namespace Hybridly\Refining\Contracts;

use Hybridly\Refining\Filters\QueryFilter;
use Illuminate\Contracts\Database\Eloquent\Builder;

interface Filter
{
    /**
     * Applies the filter onto the builder.
     */
    public function apply(Builder $builder, QueryFilter $filter, string $property): void;
}
