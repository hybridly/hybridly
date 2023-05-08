<?php

namespace Hybridly\Refining\Contracts;

use Illuminate\Contracts\Database\Eloquent\Builder;

/**
 * @template TModelClass of \Illuminate\Contracts\Database\Eloquent\Model
 */
interface Filter
{
    /**
     * Applies the filter.
     *
     * @param \Illuminate\Contracts\Database\Eloquent\Builder<TModelClass> $builder
     */
    public function __invoke(Builder $builder, mixed $value, string $property): void;

    /**
     * Gets the type of the filter. Used by the front-end.
     */
    public function getType(): string;
}
