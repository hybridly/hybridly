<?php

namespace Hybridly\Refining\Filters;

use Hybridly\Refining\Concerns\SupportsRelationConstraints;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class TextFilter extends BaseFilter
{
    use SupportsRelationConstraints;

    protected function setUp(): void
    {
        $this->type('text');

        $this->supportedOperators([
            Operator::EQUALS,
            Operator::NOT_EQUALS,
            Operator::CONTAINS,
            Operator::NOT_CONTAINS,
            Operator::BEGINS_WITH,
            Operator::ENDS_WITH,
            Operator::IS_EMPTY,
            Operator::IS_NOT_EMPTY,
            Operator::IS_NULL,
            Operator::IS_NOT_NULL,
        ]);

        $this->defaultOperator(Operator::EQUALS);

        $this->appendMetadata(fn () => [
            'current_value_label' => Str::limit($this->filter?->value, limit: 30),
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
                    Operator::CONTAINS => $builder->whereRaw(
                        "LOWER({$qualifiedColumn}) LIKE ?",
                        ['%' . mb_strtolower($value, 'UTF8') . '%'],
                        $boolean,
                    ),
                    Operator::NOT_CONTAINS => $builder->whereRaw(
                        "LOWER({$qualifiedColumn}) NOT LIKE ?",
                        ['%' . mb_strtolower($value, 'UTF8') . '%'],
                        $boolean,
                    ),
                    Operator::BEGINS_WITH => $builder->where($qualifiedColumn, 'LIKE', "{$value}%", $boolean),
                    Operator::ENDS_WITH => $builder->where($qualifiedColumn, 'LIKE', "%{$value}", $boolean),
                    Operator::IS_EMPTY => $builder->where(function (Builder $query) use ($qualifiedColumn) {
                        $query->whereNull($qualifiedColumn)->orWhere($qualifiedColumn, '=', '');
                    }, boolean: $boolean),
                    Operator::IS_NOT_EMPTY => $builder->where(function (Builder $query) use ($qualifiedColumn) {
                        $query->whereNotNull($qualifiedColumn)->where($qualifiedColumn, '!=', '');
                    }, boolean: $boolean),
                    Operator::IS_NULL => $builder->whereNull($qualifiedColumn, $boolean),
                    Operator::IS_NOT_NULL => $builder->whereNotNull($qualifiedColumn, $boolean),
                };
            },
        );
    }
}
