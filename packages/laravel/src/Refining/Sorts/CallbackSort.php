<?php

namespace Hybridly\Refining\Sorts;

use Hybridly\Components\Concerns\EvaluatesClosures;
use Hybridly\Refining\Contracts\Sort as SortContract;
use Illuminate\Contracts\Database\Eloquent\Builder;

class CallbackSort implements SortContract
{
    use EvaluatesClosures;

    private function __construct(
        protected \Closure $callback,
    ) {
    }

    public function __invoke(Builder $builder, string $direction, string $property): void
    {
        $this->evaluate($this->callback, [
            'builder' => $builder,
            'property' => $property,
        ]);
    }

    /**
     * Sorts using the specified query.
     */
    public static function make(string $name, \Closure $callback): Sort
    {
        return new Sort(
            sort: new static($callback),
            property: $name,
            alias: null,
        );
    }
}
