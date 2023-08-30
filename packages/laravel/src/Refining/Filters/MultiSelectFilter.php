<?php

namespace Hybridly\Refining\Filters;

use Hybridly\Refining\Concerns\SupportsRelationConstraints;
use Hybridly\Refining\Contracts\Filter as FilterContract;
use Hybridly\Refining\Filters\Filter;
use Illuminate\Contracts\Database\Eloquent\Builder;

class MultiSelectFilter implements FilterContract
{
    use SupportsRelationConstraints;

    private function __construct(
        protected array $options,
    ) {
    }

    public function getType(): string
    {
        return 'select';
    }

    public function __invoke(Builder $builder, string $value, string $property): void
    {
        $value = explode(',', $value);
        $options = array_is_list($this->options)
            ? $this->options
            : array_keys($this->options);

        if (empty(array_intersect($value, $options))) {
            return;
        }

        if (array_is_list($this->options)) {
            $value = collect($value)
                ->map(fn ($v) => $this->options[$v] ?? null)
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

    public static function make(string $property, array $options, ?string $alias = null): Filter
    {
        return (new Filter(
            filter: new static($options),
            property: $property,
            alias: $alias,
        ))->metadata([
            'options' => $options,
        ]);
    }
}
