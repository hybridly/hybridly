<?php

namespace Hybridly\Refining\Filters;

use Hybridly\Refining\Concerns\SupportsRelationConstraints;
use Illuminate\Contracts\Database\Eloquent\Builder;

class SelectFilter extends BaseFilter
{
    use SupportsRelationConstraints;

    protected \Closure|string $operator = '=';
    protected \Closure|array $options = [];
    protected null|\Closure|bool $isMultiple = false;

    protected function setUp(): void
    {
        $this->type('select');
    }

    public static function make(string $property, ?string $alias = null, \Closure|array $options = []): static
    {
        $static = resolve(static::class, [
            'property' => $property,
            'alias' => $alias,
        ]);

        return $static
            ->options($options)
            ->configure();
    }

    public function apply(Builder $builder, mixed $value, string $property): void
    {
        if ($this->isMultiple()) {
            $this->applyMultipleSelectQuery($builder, $value, $property);

            return;
        }

        $this->applySingleSelectQuery($builder, $value, $property);
    }

    /**
     * Defines the operator used for the filter.
     *
     * @default `=`
     */
    public function operator(\Closure|string $operator): static
    {
        $this->operator = $operator ?? '=';

        return $this;
    }

    /**
     * Defines the options for this filter.
     */
    public function options(\Closure|array $options): static
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Defines whether multiple choices can be selected.
     */
    public function multiple(\Closure|bool $condition = true): static
    {
        $this->isMultiple = $condition;

        return $this;
    }

    protected function applyMultipleSelectQuery(Builder $builder, mixed $value, string $property): void
    {
        $options = $this->getOptions();
        $value = explode(',', $value);

        $allowedOptions = array_is_list($options)
            ? $options
            : array_keys($options);

        if (empty(array_intersect($value, $allowedOptions))) {
            return;
        }

        if (!array_is_list($options)) {
            $value = collect($value)
                ->map(fn ($v) => $options[$v] ?? null)
                ->filter();
        }

        $this->applyRelationConstraint(
            builder: $builder,
            property: $property,
            callback: fn (Builder $builder, string $column) => $builder->whereIn(
                column: $builder->qualifyColumn($column),
                values: $value,
            ),
        );
    }

    protected function applySingleSelectQuery(Builder $builder, mixed $value, string $property): void
    {
        $options = $this->getOptions();
        $allowedOptions = array_is_list($options)
            ? $options
            : array_keys($options);

        if (!\in_array($value, $allowedOptions, strict: false)) {
            return;
        }

        $value = array_is_list($options)
            ? $value
            : $options[$value] ?? null;

        $this->applyRelationConstraint(
            builder: $builder,
            property: $property,
            callback: fn (Builder $builder, string $column) => $builder->where(
                column: $builder->qualifyColumn($column),
                operator: $this->getOperator(),
                value: $value,
            ),
        );
    }

    protected function getOptions(): array
    {
        return $this->evaluate($this->options);
    }

    protected function getOperator(): string
    {
        return $this->evaluate($this->operator);
    }

    protected function isMultiple(): bool
    {
        return $this->evaluate($this->isMultiple);
    }
}
