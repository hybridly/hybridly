<?php

namespace Hybridly\Refining\Sorts;

use Hybridly\Components\Concerns\EvaluatesClosures;
use Hybridly\Refining\Contracts\Sort as SortContract;
use Illuminate\Contracts\Database\Eloquent\Builder;

class CallbackSort implements SortContract
{
    use EvaluatesClosures;

    private function __construct(
        protected null|SortContract|\Closure $classOrCallback,
        protected array $parameters = [],
    ) {
        if (\is_string($classOrCallback)) {
            $this->classOrCallback = resolve($this->classOrCallback);
        }
    }

    public function __invoke(Builder $builder, string $direction, string $property): void
    {
        $this->evaluate($this->classOrCallback, [
            'builder' => $builder,
            'direction' => $direction,
            'property' => $property,
            ...$this->parameters,
        ]);
    }

    /**
     * Sorts using the specified query.
     */
    public static function make(string $name, \Closure $callback, array $parameters = []): Sort
    {
        return new Sort(
            sort: new static($callback, $parameters),
            property: $name,
            alias: null,
        );
    }
}
