<?php

namespace Hybridly\Refining\Filters;

use BackedEnum;
use Hybridly\Refining\Concerns\SupportsRelationConstraints;
use Hybridly\Refining\Contracts\Filter as FilterContract;
use Illuminate\Contracts\Database\Eloquent\Builder;

class EnumFilter implements FilterContract
{
    use SupportsRelationConstraints;

    private function __construct(
        protected string $enum,
        protected string $operator,
    ) {
        if (!is_a($enum, BackedEnum::class, allow_string: true)) {
            throw new \InvalidArgumentException("[{$enum}] is not a backed enum.");
        }
    }

    public function __invoke(Builder $builder, string|BackedEnum $value, string $property): void
    {
        if (!$value instanceof BackedEnum) {
            $value = $this->enum::tryFrom($value);
        }

        if (!$value) {
            return;
        }

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
        return 'enum';
    }

    public static function make(string $property, string $enum, string $operator = '=', ?string $alias = null): Filter
    {
        return new Filter(
            filter: new static($enum, $operator),
            property: $property,
            alias: $alias,
        );
    }
}
