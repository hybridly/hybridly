<?php

namespace Hybridly\Refining\Contracts;

use Illuminate\Contracts\Database\Eloquent\Builder;

/**
 * @template TModelClass of \Illuminate\Contracts\Database\Eloquent\Model
 */
interface Sort
{
    /**
     * Applies the sort.
     *
     * @param \Illuminate\Contracts\Database\Eloquent\Builder<TModelClass> $builder
     */
    public function __invoke(Builder $builder, string $direction, string $property): void;
}
