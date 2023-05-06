<?php

namespace Hybridly\Refining\Contracts;

use Illuminate\Contracts\Database\Eloquent\Builder;

/**
 * @template TModelClass of \Illuminate\Database\Eloquent\Model
 */
interface Sort
{
    /**
     * Applies the sort.
     *
     * @param \Illuminate\Database\Eloquent\Builder<TModelClass> $builder
     */
    public function __invoke(Builder $builder, string $direction, string $property): void;
}
