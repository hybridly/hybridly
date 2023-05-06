<?php

namespace Hybridly\Refining\Filters;

use Hybridly\Refining\Contracts\Filter as FilterContract;
use Illuminate\Contracts\Database\Eloquent\Builder;

class TrashedFilter implements FilterContract
{
    public function getType(): string
    {
        return 'trashed';
    }

    private function __construct()
    {
    }

    public function __invoke(Builder $builder, mixed $value, string $property): void
    {
        match ($value) {
            'with' => $builder->withTrashed(),
            'only' => $builder->onlyTrashed(),
            default => $builder->withoutTrashed(),
        };
    }

    /**
     * Creates a filter that configures whether trashed records should be queried.
     */
    public static function make(string $name = 'trashed', ?string $alias = null): Filter
    {
        return new Filter(
            filter: new static(),
            property: $name,
            alias: $alias,
        );
    }
}
