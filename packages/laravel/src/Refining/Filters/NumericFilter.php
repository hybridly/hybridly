<?php

namespace Hybridly\Refining\Filters;

use Hybridly\Refining\Concerns\SupportsRelationConstraints;
use Illuminate\Contracts\Database\Eloquent\Builder;

class NumericFilter extends BaseFilter
{
    use SupportsRelationConstraints;

    protected function setUp(): void
    {
        $this->type('numeric');

        $this->supportedOperators([
            Operator::EQUALS,
            Operator::NOT_EQUALS,
            Operator::GREATER_THAN,
            Operator::GREATER_THAN_OR_EQUAL,
            Operator::LESS_THAN,
            Operator::LESS_THAN_OR_EQUAL,
            Operator::BETWEEN,
            Operator::NOT_BETWEEN,
            Operator::IS_NULL,
            Operator::IS_NOT_NULL,
        ]);

        $this->defaultOperator(Operator::EQUALS);

        $this->appendMetadata(fn () => [
            'current_value_label' => $this->filter?->value,
        ]);
    }

    public static function make(string $property, ?string $alias = null): static
    {
        return resolve(static::class, [
            'property' => $property,
            'alias' => $alias,
        ]);
    }

    public function apply(Builder $builder, QueryFilter $filter, string $property): void
    {
        $value = $filter->value;

        $this->applyRelationConstraint(
            builder: $builder,
            property: $property,
            callback: function (Builder $builder, string $column, bool $isRelation) use ($value) {
                $qualifiedColumn = $this->qualifyColumn($builder, $column);
                $boolean = $isRelation ? 'and' : $this->getQueryBoolean();

                match ($this->resolveOperator()) {
                    Operator::EQUALS => $builder->where($qualifiedColumn, '=', $value, $boolean),
                    Operator::NOT_EQUALS => $builder->where($qualifiedColumn, '!=', $value, $boolean),
                    Operator::GREATER_THAN => $builder->where($qualifiedColumn, '>', $value, $boolean),
                    Operator::GREATER_THAN_OR_EQUAL => $builder->where($qualifiedColumn, '>=', $value, $boolean),
                    Operator::LESS_THAN => $builder->where($qualifiedColumn, '<', $value, $boolean),
                    Operator::LESS_THAN_OR_EQUAL => $builder->where($qualifiedColumn, '<=', $value, $boolean),
                    Operator::BETWEEN => $builder->whereBetween($qualifiedColumn, $value, $boolean),
                    Operator::NOT_BETWEEN => $builder->whereNotBetween($qualifiedColumn, $value, $boolean),
                    Operator::IS_NULL => $builder->whereNull($qualifiedColumn, $boolean),
                    Operator::IS_NOT_NULL => $builder->whereNotNull($qualifiedColumn, $boolean),
                };
            },
        );
    }
}
