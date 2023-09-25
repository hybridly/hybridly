<?php

namespace Hybridly\Refining\Sorts;

use Illuminate\Contracts\Database\Eloquent\Builder;

class Sort extends BaseSort
{
    public static function make(string $property, ?string $alias = null): static
    {
        $static = resolve(static::class, [
            'property' => $property,
            'alias' => $alias,
        ]);

        return $static->configure();
    }

    public function apply(Builder $builder, string $direction, string $property): void
    {
        $builder->orderBy(
            column: $builder->qualifyColumn($property),
            direction: $direction,
        );
    }
}
