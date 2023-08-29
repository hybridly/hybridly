<?php

namespace Hybridly\Refining\Filters;

use Hybridly\Refining\Concerns\SupportsRelationConstraints;
use Hybridly\Refining\Contracts\Filter as FilterContract;
use Illuminate\Contracts\Database\Eloquent\Builder;

class BooleanFilter implements FilterContract
{
    use SupportsRelationConstraints;

    private function __construct(
        protected string $operator,
    ) {
    }

    public function getType(): string
    {
        return 'boolean';
    }

    public function __invoke(Builder $builder, mixed $value, string $property): void
    {
        $value = match (strtolower($value)) {
            'true', '1', 'yes', 'y' => true,
            'false', '0', 'no', 'n' => false,
            default => null,
        };

        if (\is_null($value)) {
            return;
        }

        $this->applyRelationConstraint(
            builder: $builder,
            property: $property,
            callback: fn (Builder $builder, string $column) => $builder->where(
                column: $builder->qualifyColumn($column),
                operator: $this->operator,
                value: $value,
            ),
        );
    }

    /**
     * Creates a filter that checks the specified property against a boolean property.
     */
    public static function make(string $property, string $operator = '=', ?string $alias = null): Filter
    {
        return new Filter(
            filter: new static($operator),
            property: $property,
            alias: $alias,
        );
    }
}
