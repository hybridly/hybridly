<?php

namespace Hybridly\Refining\Filters;

use Hybridly\Refining\Concerns\SupportsRelationConstraints;
use Illuminate\Contracts\Database\Eloquent\Builder;

class BooleanFilter extends BaseFilter
{
    use SupportsRelationConstraints;

    protected function setUp(): void
    {
        $this->type('boolean');
    }

    public static function make(string $property, ?string $alias = null): static
    {
        return resolve(static::class, [
            'property' => $property,
            'alias' => $alias,
        ]);
    }

    public function apply(Builder $builder, mixed $value, string $property): void
    {
        $value = filter_var($value, \FILTER_VALIDATE_BOOLEAN, \FILTER_NULL_ON_FAILURE);

        if (\is_null($value)) {
            return;
        }

        $this->applyRelationConstraint(
            builder: $builder,
            property: $property,
            callback: fn (Builder $builder, string $column) => $builder->where(
                column: $this->qualifyColumn($builder, $column),
                operator: '=',
                value: $value,
                boolean: $this->getQueryBoolean(),
            ),
        );
    }
}
