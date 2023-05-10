<?php

namespace Hybridly\Refining\Filters;

use Hybridly\Components\Concerns\EvaluatesClosures;
use Hybridly\Refining\Contracts\Filter as FilterContract;
use Illuminate\Contracts\Database\Eloquent\Builder;

class CallbackFilter implements FilterContract
{
    use EvaluatesClosures;

    private function __construct(
        protected null|FilterContract|\Closure $classOrCallback,
        protected array $parameters = [],
    ) {
        if (\is_string($classOrCallback)) {
            $this->classOrCallback = resolve($this->classOrCallback);
        }
    }

    public function getType(): string
    {
        if ($this->classOrCallback instanceof FilterContract) {
            return $this->classOrCallback->getType();
        }

        return 'callback';
    }

    public function __invoke(Builder $builder, mixed $value, string $property): void
    {
        $this->evaluate($this->classOrCallback, [
            'builder' => $builder,
            'value' => $value,
            'property' => $property,
            ...$this->parameters,
        ]);
    }

    /**
     * Creates a filter that uses a callback or invokable class to filter records.
     */
    public static function make(string $property, string|\Closure $callback, array $parameters = [], ?string $alias = null): Filter
    {
        return new Filter(
            filter: new static(
                classOrCallback: $callback,
                parameters: $parameters,
            ),
            property: $property,
            alias: $alias,
        );
    }
}
