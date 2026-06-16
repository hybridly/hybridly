<?php

namespace Hybridly\Refining\Filters;

use Hybridly\Refining\Concerns\SupportsRelationConstraints;
use Hybridly\Refining\Filters\Operator;
use Illuminate\Contracts\Database\Eloquent\Builder;

class BooleanFilter extends BaseFilter
{
    use SupportsRelationConstraints;

    protected ?\Closure $trueQuery = null;
    protected ?\Closure $falseQuery = null;
    protected string|\Closure|null $trueLabel = null;
    protected string|\Closure|null $falseLabel = null;

    protected function setUp(): void
    {
        $this->type('boolean');

        $this->supportedOperators([
            Operator::EQUALS,
            Operator::NOT_EQUALS,
        ]);

        $this->defaultOperator(Operator::EQUALS);

        $this->appendMetadata(function () {
            $trueLabel = $this->trueLabel ? $this->evaluate($this->trueLabel) : null;
            $falseLabel = $this->falseLabel ? $this->evaluate($this->falseLabel) : null;

            return array_filter([
                'true_label' => $trueLabel,
                'false_label' => $falseLabel,
                'current_value_label' => match ($this->normalizeValue($this->filter?->value)) {
                    true => $trueLabel,
                    false => $falseLabel,
                    default => null,
                },
            ]);
        });
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
        $value = $this->normalizeValue($filter->value);

        if (\is_null($value)) {
            return;
        }

        if ($value === true && $this->trueQuery !== null) {
            $this->evaluate(
                value: $this->trueQuery,
                named: [
                    'builder' => $builder,
                    'query' => $builder,
                    'value' => $value,
                    'property' => $property,
                ],
                typed: [
                    Builder::class => $builder,
                ],
            );

            return;
        }

        if ($value === false && $this->falseQuery !== null) {
            $this->evaluate(
                value: $this->falseQuery,
                named: [
                    'builder' => $builder,
                    'query' => $builder,
                    'value' => $value,
                    'property' => $property,
                ],
                typed: [
                    Builder::class => $builder,
                ],
            );

            return;
        }

        $this->applyRelationConstraint(
            builder: $builder,
            property: $property,
            callback: fn (Builder $builder, string $column) => $builder->where(
                column: $this->qualifyColumn($builder, $column),
                operator: match ($this->resolveOperator()) {
                    Operator::NOT_EQUALS => '!=',
                    default => '=',
                },
                value: $value,
                boolean: $this->getQueryBoolean(),
            ),
        );
    }

    /**
     * Defines the queries to apply based on the boolean state.
     */
    public function queries(?\Closure $true = null, ?\Closure $false = null): static
    {
        $this->trueQuery = $true;
        $this->falseQuery = $false;

        return $this;
    }

    /**
     * Defines the labels for the true and false states.
     */
    public function labels(string|\Closure|null $true = null, string|\Closure|null $false = null): static
    {
        if ($true !== null) {
            $this->trueLabel($true);
        }

        if ($false !== null) {
            $this->falseLabel($false);
        }

        return $this;
    }

    /**
     * Defines the label for the true state.
     */
    public function trueLabel(string|\Closure $label): static
    {
        $this->trueLabel = $label;

        return $this;
    }

    /**
     * Defines the label for the false state.
     */
    public function falseLabel(string|\Closure $label): static
    {
        $this->falseLabel = $label;

        return $this;
    }

    /**
     * Normalizes the input value to true, false, or null.
     */
    protected function normalizeValue(mixed $value): ?bool
    {
        if ($value === null) {
            return null;
        }

        return filter_var($value, \FILTER_VALIDATE_BOOLEAN, \FILTER_NULL_ON_FAILURE);
    }
}
