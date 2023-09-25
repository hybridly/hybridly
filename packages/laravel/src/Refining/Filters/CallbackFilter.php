<?php

namespace Hybridly\Refining\Filters;

use Hybridly\Components\Concerns\EvaluatesClosures;
use Illuminate\Contracts\Database\Eloquent\Builder;

class CallbackFilter extends BaseFilter
{
    use EvaluatesClosures;

    protected string|object $invokableClassOrClosure;

    protected function setUp(): void
    {
        $this->type(function () {
            $filter = $this->getFilter();

            if (\is_object($filter) && method_exists($filter, 'getType')) {
                return $this->evaluate($filter->getType(...));
            }

            return 'callback';
        });
    }

    public static function make(string $name, string|object $callback): static
    {
        $static = resolve(static::class, ['property' => $name]);
        $static->filter($callback);

        return $static->configure();
    }

    public function apply(Builder $builder, mixed $value, string $property): void
    {
        // TODO: Get the typehinted type of `$value` in the closure,
        // and attempt to cast our `$value` to the target type
        $this->evaluate(
            value: $this->getFilter(),
            named: [
                'builder' => $builder,
                'value' => $value,
                'property' => $property,
            ],
            typed: [
                Builder::class => $builder,
            ],
        );
    }

    /**
     * Defines the callback or the invokable class that will filter the query.
     */
    public function filter(string|object $filter): static
    {
        $this->invokableClassOrClosure = $filter;

        return $this;
    }

    protected function getFilter(): object
    {
        if (\is_string($this->invokableClassOrClosure) && class_exists($this->invokableClassOrClosure)) {
            return resolve($this->invokableClassOrClosure);
        }

        return $this->invokableClassOrClosure;
    }
}
