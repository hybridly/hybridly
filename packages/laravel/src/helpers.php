<?php

namespace Hybridly;

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
     */
    function is_hybrid(Request $request = null): bool
    {
        return hybridly()->isHybrid($request);
    }
}

if (!\function_exists('Hybridly\is_partial')) {
    /**
     * Checks if the given request is a partial hybrid request.
     */
    function is_partial(Request $request = null): bool
    {
        return hybridly()->isPartial($request);
    }
}

if (!\function_exists('Hybridly\view')) {
    /**
     * Returns a hybrid view.
     */
    function view(string $component = null, array|Arrayable|DataObject $properties = []): Factory
    {
        return resolve(Hybridly::class)->view($component, $properties);
    }
}

if (!\function_exists('Hybridly\partial')) {
    /**
     * Creates a partial-only property.
     *
     * @see https://hybridly.dev/guide/partial-reloads.html
     */
    function partial(\Closure $closure): Partial
    {
        return hybridly()->partial($closure);
    }
}

if (!\function_exists('Hybridly\to_external_url')) {
    /**
     * Redirects to the given external URL.
     */
    function to_external_url(string|RedirectResponse $url): Response
    {
        return hybridly()->external($url);
    }
}
