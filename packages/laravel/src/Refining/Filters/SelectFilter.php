<?php

namespace Hybridly\Refining\Filters;

use BackedEnum;
use Hybridly\Refining\Concerns\SupportsRelationConstraints;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SelectFilter extends BaseFilter
{
    use SupportsRelationConstraints;

    protected \Closure|string $operator = '=';
    protected \Closure|string|array $options = [];
    protected null|\Closure|bool $isMultiple = false;

    protected function setUp(): void
    {
        $this->type('select');
    }

    public static function make(string $property, ?string $alias = null, \Closure|string|array $options = []): static
    {
        $static = resolve(static::class, [
            'property' => $property,
            'alias' => $alias,
        ]);

        return $static->options($options);
    }

    public function apply(Builder $builder, mixed $value, string $property): void
    {
        $options = $this->evaluate($this->options);

        if ($options instanceof Collection) {
            $options = $options->toArray();
        }

        if (\is_string($options) && is_a($options, BackedEnum::class, allow_string: true)) {
            $options = array_map(fn (\BackedEnum $enum) => $enum->value, $options::cases());
        }

        if (\is_string($options)) {
            throw new \InvalidArgumentException("The options for the [{$property}] filter must be either an array or a backed enum.");
        }

        $allowedOptions = array_is_list($options)
            ? $options
            : array_keys($options);

        if ($this->evaluate($this->isMultiple)) {
            $this->applyMultipleSelectQuery($builder, $value, $property, $options, $allowedOptions);

            return;
        }

        $this->applySingleSelectQuery($builder, $value, $property, $options, $allowedOptions);
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
    public function options(\Closure|string|array $options): static
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

    protected function applyMultipleSelectQuery(Builder $builder, mixed $value, string $property, array $options, array $allowedOptions): void
    {
        $value = array_map(fn ($s) => trim($s), \is_array($value) ? $value : explode(',', $value));

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
                column: $this->qualifyColumn($builder, $column),
                values: $value,
                boolean: $this->getQueryBoolean(),
            ),
        );
    }

    protected function applySingleSelectQuery(Builder $builder, mixed $value, string $property, array $options, array $allowedOptions): void
    {
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
                column: $this->qualifyColumn($builder, $column),
                operator: $this->evaluate($this->operator),
                value: $value,
                boolean: $this->getQueryBoolean(),
            ),
        );
    }
}
