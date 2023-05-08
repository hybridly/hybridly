<?php

namespace Hybridly\Refining\Filters;

use Hybridly\Components\Concerns\EvaluatesClosures;
use Hybridly\Refining\Contracts\Filter as FilterContract;
use Illuminate\Contracts\Database\Eloquent\Builder;

class CallbackFilter implements FilterContract
{
    use EvaluatesClosures;

    protected ?FilterContract $class = null;
    protected ?\Closure $callback = null;

    private function __construct(string|\Closure $classOrCallback)
    {
        if (\is_string($classOrCallback)) {
            $this->class = resolve($this->callback);
        } else {
            $this->callback = $classOrCallback;
        }
    }

    public function getType(): string
    {
        if ($this->class) {
            return $this->class->getType();
        }

        return 'callback';
    }

    public function __invoke(Builder $builder, mixed $value, string $property): void
    {
        if ($this->class) {
            $this->class->__invoke($builder, $value, $property);

            return;
        }

        $this->evaluate($this->callback, [
            'builder' => $builder,
            'query' => $builder,
            'value' => $value,
            'property' => $property,
        ]);
    }

    /**
     * Creates a filter that uses a callback or invokable class to filter records.
     */
    public static function make(string $property, string|\Closure $callback, ?string $alias = null): Filter
    {
        return new Filter(
            filter: new static($callback),
            property: $property,
            alias: $alias,
        );
    }
}
