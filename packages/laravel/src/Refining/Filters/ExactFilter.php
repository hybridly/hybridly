<?php

namespace Hybridly\Refining\Filters;

use Hybridly\Refining\Concerns\SupportsRelationConstraints;
use Hybridly\Refining\Contracts\Filter as FilterContract;
use Illuminate\Contracts\Database\Eloquent\Builder;

class ExactFilter implements FilterContract
{
    use SupportsRelationConstraints;

    private function __construct()
    {
    }

    public function __invoke(Builder $builder, mixed $value, string $property): void
    {
        $this->applyRelationConstraint(
            builder: $builder,
            property: $property,
            callback: fn (Builder $builder, string $column) => $builder->where(
                column: $builder->qualifyColumn($column),
                operator: '=',
                value: $value,
            ),
        );
    }

    public function getType(): string
    {
        return 'exact';
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
