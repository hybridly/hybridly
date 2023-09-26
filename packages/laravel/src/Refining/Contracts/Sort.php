<?php

namespace Hybridly\Refining\Contracts;

use Illuminate\Contracts\Database\Eloquent\Builder;

interface Sort
{
    /**
     * Applies the sort onto the builder.
     */
    public function apply(Builder $builder, string $direction, string $property): void;
}
