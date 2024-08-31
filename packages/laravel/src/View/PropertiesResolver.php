<?php

namespace Hybridly\View;

use Hybridly\Support\Arr;
use Hybridly\Support\CaseConverter;
use Hybridly\Support\Configuration\Configuration;
use Hybridly\Support\Configuration\Properties;
use Hybridly\Support\Header;
use Hybridly\Support\Properties\Deferred;
use Hybridly\Support\Properties\Hybridable;
use Hybridly\Support\Properties\IgnoreFirstLoad;
use Hybridly\Support\Properties\Mergeable;
use Hybridly\Support\Properties\Persistent;
use Hybridly\Support\Properties\Property;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceResponse;

final class PropertiesResolver
{
    public function __construct(
        protected readonly Request $request,
        protected readonly CaseConverter $caseConverter,
    ) {
    }

    public function resolve(string $component = null, array $properties = [], array $persistedByPath = []): array
    {
        $partial = \is_null($component)
            ? $this->request->headers->has(Header::PARTIAL_COMPONENT)
            : $this->request->header(Header::PARTIAL_COMPONENT) === $component;

        // First, we resolve all properties that can be converted to an array.
        // This is needed so we can work with nested properties and dot-notation.
        $properties = $this->resolveArrayableProperties($properties);

        // When the request is not partial, there are specified computation to do.
        if (!$partial) {
            // If the request is not a partial hybrid request, we want to resolve deferred properties,
            // because they will be automatically loaded back with a subsequent partial request.
            $deferred = $this->filterToPropertyPaths($properties, function (mixed $value, string $path) {
                if ($value instanceof Deferred) {
                    return $path;
                }

                return false;
            });

            // Additionally, we want to exclude properties that should not be loaded on first load.
            $properties = Arr::filterRecursive($properties, static fn ($property) => !($property instanceof IgnoreFirstLoad));
        }

        // Mergeable properties are then resolved. These are special properties
        // that will have a special merge treatment when merging on the front-end.
        $mergeable = $this->filterToPropertyPaths($properties, function (mixed $value, string $path) {
            if ($value instanceof Mergeable) {
                return $value->shouldMerge()
                    ? [$path, $value->shouldBeUnique()]
                    : false;
            }

            return false;
        });

        // Next up, we want to know which properties should always be present on
        // the response. These properties are either `Persistent` instances,
        // or they were mentionned in the `$persisted` array.
        $persisted = $this->filterToPropertyPaths($properties, function (mixed $value, string $path) use ($persistedByPath) {
            if (\in_array($path, $persistedByPath, strict: true)) {
                return $path;
            }

            if ($value instanceof Persistent) {
                return $path;
            }

            return false;
        });

        // Finally, we need to resolve property instances to an array that
        // we can recursively traverse. This is needed to include
        // or exclude properties using the dot-notation.
        $properties = $this->resolveArrayableProperties($properties);

        // The `only` and `except` headers contain json-encoded array data. We want to use them to
        // retrieve the properties whose paths they describe using dot-notation.
        // We only do that when the request is specifically for partial data though.
        if ($partial && $this->request->hasHeader(Header::PARTIAL_ONLY)) {
            $only = array_filter(json_decode($this->request->header(Header::PARTIAL_ONLY, default: ''), associative: true) ?? []);
            $only = $this->convertPartialPropertiesCase($only);
            $properties = Arr::onlyDot($properties, array_merge($only, $persisted));
        }

        if ($partial && $this->request->hasHeader(Header::PARTIAL_EXCEPT)) {
            $except = array_filter(json_decode($this->request->header(Header::PARTIAL_EXCEPT, default: ''), associative: true) ?? []);
            $except = $this->convertPartialPropertiesCase($except);
            $properties = Arr::exceptDot($properties, $except);
        }

        $properties = $this->convertOutputCase(
            // Only when we only have the properties we need, we can
            // evaluated the lazy ones. If we did it earlier, we
            // could evaluate them even if they were excluded.
            array: $this->evaluatePropertyInstances($properties),
        );

        return [$properties, $deferred ?? [], $mergeable];
    }

    /**
     * Returns an array of property paths, filtered by the specified callback.
     */
    protected function filterToPropertyPaths(array $properties, \Closure $callback, string $path = ''): array
    {
        $selected = [];

        foreach ($properties as $key => $value) {
            if ($value instanceof Hybridable) {
                $value = $value->toHybridArray();
            }

            if ($value instanceof Arrayable) {
                $value = $value->toArray();
            }

            if (\is_array($value)) {
                $selected = array_merge($selected, $this->filterToPropertyPaths($value, $callback, $path ? ("{$path}.{$key}") : $key));
            }

            if ($result = $callback($value, ($path ? ("{$path}.{$key}") : $key), $path)) {
                $selected[] = $result;
            }
        }

        return $selected;
    }

    /**
     * Resolves properties that can be converted to an array.
     */
    protected function resolveArrayableProperties(array $properties, bool $unpackDotProps = true): array
    {
        foreach ($properties as $key => $value) {
            if ($value instanceof Hybridable) {
                $value = $value->toHybridArray();
            }

            if ($value instanceof Arrayable) {
                $value = $value->toArray();
            }

            if (\is_array($value)) {
                $value = $this->resolveArrayableProperties($value, unpackDotProps: false);
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

    /**
     * Evaluates all properties recursively.
     */
    protected function evaluatePropertyInstances(array $properties): array
    {
        foreach ($properties as $key => $value) {
            if ($value instanceof Property) {
                $value = $value->__invoke();
            }

            if (\is_object($value) && \is_callable($value)) {
                $value = app()->call($value);
            }

            // This could potentially be in `resolvePropertyInstances`. Feel free to PR if needed.
            if ($value instanceof \GuzzleHttp\Promise\PromiseInterface) {
                $value = $value->wait();
            }

            // This could potentially be in `resolvePropertyInstances`. Feel free to PR if needed.
            if ($value instanceof ResourceResponse || $value instanceof JsonResource) {
                $value = $value->toResponse($this->request)->getData(true);
            }

            if (\is_array($value)) {
                $value = $this->evaluatePropertyInstances($value);
            }

            $properties[$key] = $value;
        }

        return $properties;
    }

    protected function convertPartialPropertiesCase(array $array): array
    {
        return match (Configuration::get()->properties->forceInputCase) {
            Properties::CAMEL => collect($array)->map(fn ($property) => (string) str()->camel($property))->toArray(),
            Properties::SNAKE => collect($array)->map(fn ($property) => (string) str()->snake($property))->toArray(),
            default => $array,
        };
    }

    protected function convertOutputCase(array $array): array
    {
        return match (Configuration::get()->properties->forceOutputCase) {
            Properties::SNAKE => $this->caseConverter->convert($array, 'snake'),
            Properties::CAMEL => $this->caseConverter->convert($array, 'camel'),
            default => $array
        };
    }
}
