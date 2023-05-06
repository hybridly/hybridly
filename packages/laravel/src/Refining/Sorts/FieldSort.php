<?php

namespace Hybridly\Refining\Sorts;

use Hybridly\Refining\Contracts\Sort as SortContract;
use Illuminate\Contracts\Database\Eloquent\Builder;

class FieldSort implements SortContract
{
    private function __construct()
    {
    }

    public function __invoke(Builder $builder, string $direction, string $property): void
    {
        $builder->orderBy(
            column: $builder->qualifyColumn($property),
            direction: $direction,
        );
    }

    /**
     * Sorts by the specified property.
     */
    public static function make(string $property, ?string $alias = null): Sort
    {
        return new Sort(
            sort: new static(),
            property: $property,
            alias: $alias,
        );
    }
}
