<?php

namespace Hybridly\Refining\Contracts;

use Illuminate\Contracts\Database\Eloquent\Builder;

interface Filter
{
    /**
     * Applies the filter onto the builder.
     */
    public function apply(Builder $builder, mixed $value, string $property): void;
}
