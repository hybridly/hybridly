<?php

namespace Hybridly\Refining\Filters;

use BackedEnum;
use Hybridly\Refining\Contracts\Filter as FilterContract;
use Illuminate\Contracts\Database\Eloquent\Builder;

class EnumFilter implements FilterContract
{
    private function __construct(
        protected string $enum,
        private string $operator,
    ) {
    }

    public function getType(): string
    {
        return 'enum';
    }

    public function __invoke(Builder $builder, mixed $value, string $property): void
    {
        if (!$value = $this->enum::tryFrom($value)) {
            return;
        }

        // TODO: support dot-notated relationships
        $builder->where(
            column: $builder->qualifyColumn($property),
            operator: $this->operator,
            value: $value,
        );
    }

    public static function make(string $property, string $enum, string $operator = '=', ?string $alias = null): Filter
    {
        if (!is_a($enum, BackedEnum::class, allow_string: true)) {
            throw new \InvalidArgumentException("{$enum} is not a backed enum.");
        }

        return new Filter(
            filter: new static($enum, $operator),
            property: $property,
            alias: $alias,
        );
    }
}
