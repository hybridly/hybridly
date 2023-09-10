<?php

namespace Hybridly;

use Hybridly\Support\Deferred;
use Hybridly\Support\Partial;
use Hybridly\View\Factory;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Spatie\LaravelData\Contracts\DataObject;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

if (!\function_exists('Hybridly\is_hybrid')) {
    /**
     * Checks if the given request is hybrid.
     *
     * @see https://hybridly.dev/api/laravel/functions.html#is-hybrid
     */
    function is_hybrid(Request $request = null): bool
    {
        return hybridly()->isHybrid($request);
    }
}

if (!\function_exists('Hybridly\is_partial')) {
    /**
     * Checks if the given request is a partial hybrid request.
     *
     * @see https://hybridly.dev/api/laravel/functions.html#is-partial
     */
    function is_partial(Request $request = null): bool
    {
        return hybridly()->isPartial($request);
    }
}

if (!\function_exists('Hybridly\view')) {
    /**
     * Returns a hybrid view.
     *
     * @see https://hybridly.dev/api/laravel/functions.html#view
     */
    function view(string $component = null, array|Arrayable|DataObject $properties = []): Factory
    {
        return resolve(Hybridly::class)->view($component, $properties);
    }
}

if (!\function_exists('Hybridly\properties')) {
    /**
     * Returns properties for an existing view.
     *
     * @see https://hybridly.dev/api/laravel/functions.html#properties
     */
    function properties(array|Arrayable|DataObject $properties): Factory
    {
        return resolve(Hybridly::class)->properties($properties);
    }
}

if (!\function_exists('Hybridly\partial')) {
    /**
     * Creates a partial-only property.
     *
     * @see https://hybridly.dev/api/laravel/functions.html#partial
     */
    function partial(\Closure $closure): Partial
    {
        return hybridly()->partial($closure);
    }
}

if (!\function_exists('Hybridly\deferred')) {
    /**
     * Creates a deferred property that will automatically be loaded in a subsequent partial reload.
     *
     * @see https://hybridly.dev/api/laravel/functions.html#deferred
     */
    function deferred(\Closure $closure): Deferred
    {
        return hybridly()->deferred($closure);
    }
}

if (!\function_exists('Hybridly\to_external_url')) {
    /**
     * Redirects to the given external URL.
     *
     * @see https://hybridly.dev/api/laravel/functions.html#to-external-url
     */
    function to_external_url(string|RedirectResponse $url): Response
    {
        return hybridly()->external($url);
    }
}
