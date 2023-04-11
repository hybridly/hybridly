<?php

namespace Hybridly\Refining\Filters;

use Hybridly\Refining\Contracts\Filter as FilterContract;
use Illuminate\Contracts\Database\Eloquent\Builder;

class CallbackFilter implements FilterContract
{
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
        $this->callback->__invoke($builder, $value, $property);
    }

    /**
     * Creates a filter that uses a callback to filter records.
     */
    public static function make(string $property, \Closure $callback, array $aliases = []): Filter
    {
        return new Filter(
            filter: new static($callback),
            property: $property,
            aliases: $aliases,
        );
    }
}
