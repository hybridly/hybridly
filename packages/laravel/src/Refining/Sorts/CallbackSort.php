<?php

namespace Hybridly\Refining\Sorts;

use Hybridly\Components\Concerns\EvaluatesClosures;
use Hybridly\Refining\Contracts\Sort as SortContract;
use Illuminate\Contracts\Database\Eloquent\Builder;

class CallbackSort implements SortContract
{
    use EvaluatesClosures;

    protected ?SortContract $class = null;
    protected ?\Closure $callback = null;

    private function __construct(string|\Closure $classOrCallback)
    {
        if (\is_string($classOrCallback)) {
            $this->class = resolve($this->callback);
        } else {
            $this->callback = $classOrCallback;
        }
    }

    public function __invoke(Builder $builder, string $direction, string $property): void
    {
        if ($this->class) {
            $this->class->__invoke($builder, $direction, $property);

            return;
        }

        $this->evaluate($this->callback, [
            'builder' => $builder,
            'direction' => $direction,
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
