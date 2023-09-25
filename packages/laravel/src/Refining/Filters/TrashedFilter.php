<?php

namespace Hybridly\Refining\Filters;

use Illuminate\Contracts\Database\Eloquent\Builder;

class TrashedFilter extends BaseFilter
{
    protected function setUp(): void
    {
        $this->type('trashed');
    }

    public static function make(string $alias = 'trashed'): static
    {
        $static = resolve(static::class, [
            'property' => $alias,
        ]);

        return $static->configure();
    }

    public function apply(Builder $builder, mixed $value, string $property): void
    {
        match ($value) {
            'with' => $builder->withTrashed(),
            'only' => $builder->onlyTrashed(),
            default => $builder->withoutTrashed(),
        };
    }
}
