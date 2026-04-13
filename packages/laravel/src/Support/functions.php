<?php

namespace Hybridly\Support;

use Hybridly\SerializesProperties;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Enumerable;
use Spatie\LaravelData\Contracts\TransformableData;

/**
 * Get a subset of the items from the given array, with dot notation support.
 */
function only_dot(array $array, string|array $only): array
{
    return Arr::only($array, $only) + collect(Arr::dot($array))
        ->filter(fn ($_, $key) => collect($only)->some(fn ($only) => $only === $key || str_starts_with($key, $only . '.')))
        ->undot()
        ->toArray();
}

/**
 * Get all of the given array except for a specified array of keys, with dot notation support.
 */
function except_dot(array $array, string|array $except): array
{
    return Arr::except($array, $except);
}

/**
 * Same as `array_filter`, but recursive.
 */
function filter_recursive(array $array, ?callable $callback = null): array
{
    $array = \is_callable($callback) ? array_filter($array, $callback) : array_filter($array);

    foreach ($array as &$value) {
        if ($value instanceof SerializesProperties) {
            $value = $value->toHybridArray();
        }

        if ($value instanceof Arrayable) {
            $value = $value->toArray();
        }

        if (\is_array($value)) {
            $value = filter_recursive($value, $callback);
        }
    }

    return $array;
}

function resolve_arrayable_properties(array $properties, bool $unpackDotProps = true): array
{
    foreach ($properties as $key => $value) {
        if ($value instanceof Enumerable) {
            $value = $value->all();
        }

        if ($value instanceof TransformableData) {
            $value = $value->all();
        }

        if ($value instanceof SerializesProperties) {
            $value = $value->toHybridArray();
        }

        if ($value instanceof Arrayable) {
            $value = $value->toArray();
        }

        if (\is_array($value)) {
            $value = resolve_arrayable_properties($value, unpackDotProps: false);
        }

        if ($unpackDotProps && str_contains($key, '.')) {
            data_set($properties, $key, $value);
            unset($properties[$key]);
        } else {
            $properties[$key] = $value;
        }
    }

    return $properties;
}
