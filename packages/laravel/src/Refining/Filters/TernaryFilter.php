<?php

namespace Hybridly\Refining\Filters;

use Hybridly\Components\Concerns\EvaluatesClosures;
use Hybridly\Refining\Refine;
use Illuminate\Contracts\Database\Eloquent\Builder;

class TernaryFilter extends BaseFilter
{
    use EvaluatesClosures;

    protected ?\Closure $trueQuery = null;
    protected ?\Closure $falseQuery = null;
    protected ?\Closure $blankQuery = null;
    protected string|\Closure|null $placeholder = null;
    protected string|\Closure|null $trueLabel = null;
    protected string|\Closure|null $falseLabel = null;

    protected function setUp(): void
    {
        $this->type('ternary');
        $this->appendMetadata(fn () => array_filter([
            'true_label' => $this->trueLabel ? $this->evaluate($this->trueLabel) : null,
            'false_label' => $this->falseLabel ? $this->evaluate($this->falseLabel) : null,
            'placeholder' => $this->placeholder ? $this->evaluate($this->placeholder) : null,
        ]));
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
        $normalizedValue = $this->normalizeValue($value);

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
        $this->value = $refiner->getFilterValueFromRequest($this->property, $this->alias);

        // If value is null/blank and we have a blank query, apply it
        if ($this->value === null && $this->blankQuery !== null) {
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

        parent::refine($refiner, $builder);
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
