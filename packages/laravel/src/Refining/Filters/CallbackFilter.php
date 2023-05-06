<?php

namespace Hybridly\Refining\Filters;

use Hybridly\Components\Concerns\EvaluatesClosures;
use Hybridly\Refining\Contracts\Filter as FilterContract;
use Illuminate\Contracts\Database\Eloquent\Builder;

class CallbackFilter implements FilterContract
{
    use EvaluatesClosures;

    private function __construct(
        protected \Closure $callback,
    ) {
    }

    public function getType(): string
    {
        return 'callback';
    }

    public function __invoke(Builder $builder, mixed $value, string $property): void
    {
        $this->evaluate($this->callback, [
            'builder' => $builder,
            'query' => $builder,
            'value' => $value,
            'property' => $property,
        ]);
    }

    /**
     * Creates a filter that uses a callback to filter records.
     */
    public static function make(string $property, \Closure $callback, ?string $alias = null): Filter
    {
        return new Filter(
            filter: new static($callback),
            property: $property,
            alias: $alias,
        );
    }
}
