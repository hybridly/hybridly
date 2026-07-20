<?php

namespace Hybridly\Refining\Filters;

use Hybridly\Components\Concerns\EvaluatesClosures;
use Illuminate\Contracts\Database\Eloquent\Builder;
use ReflectionNamedType;
use ReflectionParameter;

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

        return $static;
    }

    public function apply(Builder $builder, QueryFilter $filter, string $property): void
    {
        $filter = $this->castValueToExpectedType($filter->value);

        $this->evaluate(
            value: $this->getFilter(),
            named: [
                'builder' => $builder,
                'value' => $filter,
                'property' => $property,
            ],
            typed: [
                Builder::class => $builder,
            ],
        );
    }

    protected function resolveQueryFilter(QueryFilter $filter): ?QueryFilter
    {
        if ($filter->value !== null) {
            return $filter;
        }

        $parameter = $this->getValueParameter();

        if ($parameter === null || ! $parameter->hasType()) {
            return $filter;
        }

        return $parameter->getType()->allowsNull() ? $filter : null;
    }

    /**
     * Attempts to cast the value to the type expected by the closure's $value parameter.
     */
    protected function castValueToExpectedType(array|string|int|null $value): mixed
    {
        if ($value === null) {
            return $value;
        }

        $parameter = $this->getValueParameter();

        if ($parameter === null || ! $parameter->hasType()) {
            return $value;
        }

        $type = $parameter->getType();
        if ($type instanceof ReflectionNamedType) {
            return $this->castToType($value, $type);
        }

        return $value;
    }

    /**
     * Casts a value to the specified reflection type.
     */
    protected function castToType(array|string|int $value, ReflectionNamedType $type): mixed
    {
        if ($value === null && $type->allowsNull()) {
            return null;
        }

        $typeName = $type->getName();
        if ($typeName === 'mixed' || ! $type->isBuiltin()) {
            return $value;
        }

        return match ($typeName) {
            'int' => (int) $value,
            'float' => (float) $value,
            'string' => (string) $value,
            'bool' => filter_var($value, \FILTER_VALIDATE_BOOLEAN, \FILTER_NULL_ON_FAILURE) ?? (bool) $value,
            'array' => \is_array($value) ? $value : [$value],
            default => $value,
        };
    }

    private function getValueParameter(): ?ReflectionParameter
    {
        $filter = $this->getFilter();
        $reflection = $filter instanceof \Closure
            ? new \ReflectionFunction($filter)
            : new \ReflectionMethod($filter, '__invoke');

        return array_find(
            $reflection->getParameters(),
            fn (ReflectionParameter $parameter): bool => $parameter->getName() === 'value',
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
