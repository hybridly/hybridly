<?php

namespace Hybridly\Support;

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

    public function resolve(string $component, array $properties, array $persisted): array
    {
        $partial = $this->request->header(Header::PARTIAL_COMPONENT) === $component;

        if (!$partial) {
            $properties = Arr::filterRecursive($properties, static fn ($property) => !($property instanceof Partial));
        }

        // First, we need to resolve property instances to an array that
        // we can recursively traverse. This is needed to include
        // or exclude properties using the dot-notation.
        $properties = $this->resolvePropertyInstances($properties);

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

        return $this->convertOutputCase(
            // Only when we only have the properties we need, we can
            // evaluated the lazy ones. If we did it earlier, we
            // could evaluate them even if they were excluded.
            array: $this->evaluatePropertyInstances($properties),
        );
    }

    /**
     * Resolves all specific property instances to an array.
     */
    protected function resolvePropertyInstances(array $properties, bool $unpackDotProps = true): array
    {
        foreach ($properties as $key => $value) {
            if ($value instanceof Arrayable) {
                $value = $value->toArray();
            }

            if (\is_array($value)) {
                $value = $this->resolvePropertyInstances($value, unpackDotProps: false);
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
        return match (config('hybridly.force_case.input')) {
            'camel' => collect($array)->map(fn ($property) => (string) str()->camel($property))->toArray(),
            'snake' => collect($array)->map(fn ($property) => (string) str()->snake($property))->toArray(),
            default => $array,
        };
    }

    protected function convertOutputCase(array $array): array
    {
        return match (config('hybridly.force_case.output')) {
            'snake' => $this->caseConverter->convert($array, 'snake'),
            'camel' => $this->caseConverter->convert($array, 'camel'),
            default => $array
        };
    }
}
