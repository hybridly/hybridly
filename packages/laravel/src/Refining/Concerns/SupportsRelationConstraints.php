<?php

namespace Hybridly\Refining\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

trait SupportsRelationConstraints
{
    protected function applyRelationConstraint(Builder $builder, string $property, \Closure $callback): void
    {
        if (!str_contains($property, '.')) {
            $callback($builder, $property, false);

            return;
        }

        [$relation, $property] = collect(explode('.', $property))
            ->pipe(fn (Collection $parts) => [
                $parts->except(\count($parts) - 1)->implode('.'),
                $parts->last(),
            ]);

        $method = $this->getQueryBoolean() === 'or'
            ? 'orWhereHas'
            : 'whereHas';

        $builder->{$method}($relation, function (Builder $builder) use ($property, $callback) {
            if (!str_contains($property, '.')) {
                $callback($builder, $property, true);
            } else {
                $this->applyRelationConstraint($builder, $property, $callback);
            }
        });
    }
}
