<?php

namespace Hybridly\Refining\Filters;

use Hybridly\Refining\Refine;
use Illuminate\Contracts\Database\Eloquent\Builder;

class TernaryFilter extends BaseFilter
{
    protected ?\Closure $trueQuery = null;
    protected ?\Closure $falseQuery = null;
    protected ?\Closure $blankQuery = null;
    protected string|\Closure|null $placeholder = null;
    protected string|\Closure|null $trueLabel = null;
    protected string|\Closure|null $falseLabel = null;

    protected function setUp(): void
    {
        $this->type('ternary');

        $this->supportedOperators([
            Operator::EQUALS,
        ]);

        $this->defaultOperator(Operator::EQUALS);

        $this->appendMetadata(function () {
            $trueLabel = $this->trueLabel ? $this->evaluate($this->trueLabel) : null;
            $falseLabel = $this->falseLabel ? $this->evaluate($this->falseLabel) : null;
            $placeholder = $this->placeholder ? $this->evaluate($this->placeholder) : null;

            return array_filter([
                'true_label' => $trueLabel,
                'false_label' => $falseLabel,
                'placeholder' => $placeholder,
                'current_value_label' => match ($this->normalizeValue($this->filter?->value)) {
                    true => $trueLabel,
                    false => $falseLabel,
                    default => $placeholder,
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
        $normalizedValue = $this->normalizeValue($filter->value);

        if ($normalizedValue === true && $this->trueQuery !== null) {
            $this->evaluate(
                value: $this->trueQuery,
                named: [
                    'builder' => $builder,
                    'query' => $builder,
                    'value' => $normalizedValue,
                    'property' => $property,
                ],
                typed: [
                    Builder::class => $builder,
                ],
            );
        }

        if ($normalizedValue === false && $this->falseQuery !== null) {
            $this->evaluate(
                value: $this->falseQuery,
                named: [
                    'builder' => $builder,
                    'query' => $builder,
                    'value' => $normalizedValue,
                    'property' => $property,
                ],
                typed: [
                    Builder::class => $builder,
                ],
            );
        }
    }

    public function refine(Refine $refiner, Builder $builder): void
    {
        $this->setRefineInstance($refiner);
        $this->filter = $this->resolveFilter($refiner);

        // If value is null/blank and we have a blank query, apply it
        if ($this->filter === null && $this->blankQuery !== null) {
            $this->evaluate(
                value: $this->blankQuery,
                named: [
                    'builder' => $builder,
                    'query' => $builder,
                    'value' => null,
                    'property' => $this->property,
                ],
                typed: [
                    Builder::class => $builder,
                ],
            );

            return;
        }

        if ($this->filter === null) {
            return;
        }

        $this->apply($builder, $this->filter, $this->property);
    }

    /**
     * Defines the queries to apply based on the ternary state.
     */
    public function queries(?\Closure $true = null, ?\Closure $false = null, ?\Closure $blank = null): static
    {
        $this->trueQuery = $true;
        $this->falseQuery = $false;
        $this->blankQuery = $blank;

        return $this;
    }

    /**
     * Defines the labels for the true and false states.
     */
    public function labels(string|\Closure|null $true = null, string|\Closure|null $false = null, string|\Closure|null $placeholder = null): static
    {
        if ($true !== null) {
            $this->trueLabel($true);
        }

        if ($false !== null) {
            $this->falseLabel($false);
        }

        if ($placeholder !== null) {
            $this->placeholder($placeholder);
        }

        return $this;
    }

    /**
     * Defines the placeholder text for the filter.
     */
    public function placeholder(string|\Closure $placeholder): static
    {
        $this->placeholder = $placeholder;

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
