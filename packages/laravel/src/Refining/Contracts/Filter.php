<?php

namespace Hybridly\Refining\Contracts;

use Illuminate\Contracts\Database\Eloquent\Builder;

interface Filter
{
    /**
     * Gets the type of the filter. Used by the front-end.
     */
    public function getType(): string;

    /**
     * Applies the filter onto the builder.
     */
    public function apply(Builder $builder, mixed $value, string $property): void;
}
