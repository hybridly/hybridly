<?php

namespace Hybridly\Components\Concerns;

use Closure;
use Illuminate\Contracts\Container\BindingResolutionException;
use ReflectionFunction;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * Code belongs to Filament PHP:
 * https://github.com/filamentphp/filament/blob/3.x/packages/support/src/Concerns/EvaluatesClosures.php
 */
trait EvaluatesClosures
{
    protected string $evaluationIdentifier;

    public function evaluate(mixed $value, array $named = [], array $typed = [], array $positional = []): mixed
    {
        if (\is_object($value) && method_exists($value, '__invoke')) {
            $value = $value->__invoke(...);
        }

        if (! ($value instanceof Closure)) {
            return $value;
        }

        $dependencies = [];

        foreach ((new ReflectionFunction($value))->getParameters() as $parameter) {
            $dependencies[] = $this->resolveClosureDependencyForEvaluation($parameter, $named, $typed, $positional);
        }

        return $value(...$dependencies);
    }

    protected function resolveTyped(string $type, array $typed): mixed
    {
        if (array_key_exists($type, $typed)) {
            return value($typed[$type]);
        }

        foreach ($typed as $key => $value) {
            if (is_a($type, $key, allow_string: true)) {
                return value($value);
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $named
     * @param  array<string, mixed>  $typed
     * @param  array<int, mixed>  $positional
     */
    protected function resolveClosureDependencyForEvaluation(ReflectionParameter $parameter, array $named, array $typed, array $positional): mixed
    {
        $parameterName = $parameter->getName();

        if (\array_key_exists($parameterName, $named)) {
            return value($named[$parameterName]);
        }

        $typedParameterClassName = $this->getTypedReflectionParameterClassName($parameter);

        if (is_string($typedParameterClassName) && ($value = $this->resolveTyped($typedParameterClassName, $typed))) {
            return $value;
        }

        // Dependencies are wrapped in an array to differentiate between null and no value.
        $defaultWrappedDependencyByName = $this->resolveDefaultClosureDependencyForEvaluationByName($parameterName);

        if (\count($defaultWrappedDependencyByName)) {
            // Unwrap the dependency if it was resolved.
            return $defaultWrappedDependencyByName[0];
        }

        if (filled($typedParameterClassName)) {
            // Dependencies are wrapped in an array to differentiate between null and no value.
            $defaultWrappedDependencyByType = $this->resolveDefaultClosureDependencyForEvaluationByType($typedParameterClassName);

            if (\count($defaultWrappedDependencyByType)) {
                // Unwrap the dependency if it was resolved.
                return $defaultWrappedDependencyByType[0];
            }
        }

        if (isset($this->evaluationIdentifier) && $parameterName === $this->evaluationIdentifier) {
            return $this;
        }

        $parameterPosition = $parameter->getPosition();

        if (\array_key_exists($parameterPosition, $positional)) {
            return value($positional[$parameterPosition]);
        }

        if (filled($typedParameterClassName)) {
            return resolve($typedParameterClassName);
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        if ($parameter->isOptional()) {
            return null;
        }

        $staticClass = static::class;

        throw new BindingResolutionException("An attempt was made to evaluate a closure for [{$staticClass}], but [\${$parameterName}] was unresolvable.");
    }

    /**
     * @return array<mixed>
     */
    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): array
    {
        return [];
    }

    /**
     * @return array<mixed>
     */
    protected function resolveDefaultClosureDependencyForEvaluationByType(string $parameterType): array
    {
        return [];
    }

    protected function getTypedReflectionParameterClassName(ReflectionParameter $parameter): ?string
    {
        $type = $parameter->getType();

        if (! ($type instanceof ReflectionNamedType)) {
            return null;
        }

        if ($type->isBuiltin()) {
            return null;
        }

        $name = $type->getName();

        $class = $parameter->getDeclaringClass();

        if (blank($class)) {
            return $name;
        }

        if ($name === 'self') {
            return $class->getName();
        }

        if ($name === 'parent' && ($parent = $class->getParentClass())) {
            return $parent->getName();
        }

        return $name;
    }
}
