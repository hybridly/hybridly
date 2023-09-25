<?php

namespace Hybridly\Refining\Sorts;

use Hybridly\Components\Concerns\EvaluatesClosures;
use Illuminate\Contracts\Database\Eloquent\Builder;

class CallbackSort extends BaseSort
{
    use EvaluatesClosures;

    protected string|object $invokableClassOrClosure;

    public static function make(string $alias, string|object $callback): static
    {
        $static = resolve(static::class, ['property' => $alias]);
        $static->sort($callback);

        return $static->configure();
    }

    public function apply(Builder $builder, string $direction, string $property): void
    {
        $this->evaluate(
            value: $this->getSort(),
            named: [
                'builder' => $builder,
                'direction' => $direction,
                'property' => $property,
            ],
            typed: [
                Builder::class => $builder,
            ],
        );
    }

    /**
     * Defines the callback or the invokable class that will sort the query.
     */
    public function sort(string|object $sort): static
    {
        $this->invokableClassOrClosure = $sort;

        return $this;
    }

    public function getSort(): object
    {
        if (\is_string($this->invokableClassOrClosure) && class_exists($this->invokableClassOrClosure)) {
            return resolve($this->invokableClassOrClosure);
        }

        return $this->invokableClassOrClosure;
    }
}
