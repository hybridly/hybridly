<?php

namespace Hybridly\Refining\Filters;

use Hybridly\Refining\Concerns\SupportsRelationConstraints;
use Illuminate\Contracts\Database\Eloquent\Builder;

class SelectFilter extends BaseFilter
{
    use SupportsRelationConstraints;

    protected \Closure|string $operator = '=';
    protected \Closure|array $options = [];

    public static function make(string $property, ?string $alias = null): static
    {
        $static = resolve(static::class, [
            'property' => $property,
            'alias' => $alias,
        ]);

        return $static->configure();
    }

    public function apply(Builder $builder, mixed $value, string $property): void
    {
        $options = $this->getOptions();
        $options = array_is_list($options)
            ? $options
            : array_keys($options);

        if (!\in_array($value, $options, strict: false)) {
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

    public function getType(): string
    {
        return 'select';
    }

    public function getOptions(): array
    {
        return $this->evaluate($this->options);
    }

    public function getOperator(): string
    {
        return $this->evaluate($this->operator);
    }
}
