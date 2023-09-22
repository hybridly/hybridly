<?php

namespace Hybridly\Refining\Filters;

use Hybridly\Refining\Concerns\SupportsRelationConstraints;
use Hybridly\Refining\Contracts\Filter as FilterContract;
use Illuminate\Contracts\Database\Eloquent\Builder;

class SelectFilter implements FilterContract
{
    use SupportsRelationConstraints;

    private function __construct(
        protected array $options,
        protected string $operator,
    ) {
    }

    public function __invoke(Builder $builder, mixed $value, string $property): void
    {
        $options = array_is_list($this->options)
            ? $this->options
            : array_keys($this->options);

        if (!\in_array($value, $options, strict: false)) {
            return;
        }

        $value = array_is_list($this->options)
            ? $value
            : $this->options[$value] ?? null;

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

    public function getType(): string
    {
        return 'select';
    }

    public static function make(string $property, array $options, string $operator = '=', ?string $alias = null): Filter
    {
        return (new Filter(
            filter: new static($options, $operator),
            property: $property,
            alias: $alias,
        ))->metadata([
            'options' => $options,
        ]);
    }
}
