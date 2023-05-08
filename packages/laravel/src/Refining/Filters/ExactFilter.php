<?php

namespace Hybridly\Refining\Filters;

use Hybridly\Refining\Contracts\Filter as FilterContract;
use Illuminate\Contracts\Database\Eloquent\Builder;

class ExactFilter implements FilterContract
{
    private function __construct()
    {
    }

    public function getType(): string
    {
        return 'exact';
    }

    public function __invoke(Builder $builder, mixed $value, string $property): void
    {
        // TODO: support dot-notated relationships
        $builder->where(
            column: $builder->qualifyColumn($property),
            operator: '=',
            value: $value,
        );
    }

    /**
     * Creates a filter that uses an exact match to filter records.
     */
    public static function make(string $property, ?string $alias = null): Filter
    {
        return new Filter(
            filter: new static(),
            property: $property,
            alias: $alias,
        );
    }
}
