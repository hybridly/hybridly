<?php

namespace Hybridly;

use Hybridly\Support\Deferred;
use Hybridly\Support\Header;
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
        $request ??= request();

        return $request->headers->has(Header::HYBRID_REQUEST);
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
        $request ??= request();

        if (!is_hybrid($request)) {
            return false;
        }

        return $request->headers->has(Header::PARTIAL_COMPONENT);
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
        return resolve(Factory::class)->view($component, $properties);
    }
}

if (!\function_exists('Hybridly\dialog')) {
    /**
     * Returns a dialog with the given properties and base view.
     *
     * @see https://hybridly.dev/api/laravel/functions.html#dialog
     */
    function dialog(string $component = null, array|Arrayable|DataObject $properties, string $base): Factory
    {
        return resolve(Factory::class)
            ->view($component, $properties)
            ->base($base);
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
        return resolve(Factory::class)->properties($properties);
    }
}

if (!\function_exists('Hybridly\partial')) {
    /**
     * Creates a partial-only property.
     *
     * @see https://hybridly.dev/api/laravel/functions.html#partial
     */
    function partial(\Closure $callback): Partial
    {
        return new Partial($callback);
    }
}

if (!\function_exists('Hybridly\deferred')) {
    /**
     * Creates a deferred property that will not be included in an initial load,
     * but will automatically be loaded in a subsequent partial reload.
     *
     * @see https://hybridly.dev/api/laravel/functions.html#deferred
     */
    function deferred(\Closure $callback): Deferred
    {
        return new Deferred($callback);
    }
}

if (!\function_exists('Hybridly\to_external_url')) {
    /**
     * Generates a response for redirecting to an external website, or a non-hybrid page.
     * This can also be used to redirect to a hybrid page when it is not known whether the current request is hybrid or not.
     *
     * @see https://hybridly.dev/api/laravel/functions.html#to-external-url
     */
    function to_external_url(string|RedirectResponse $url): Response
    {
        if ($url instanceof RedirectResponse) {
            $url = $url->getTargetUrl();
        }

        if (is_hybrid()) {
            return new Response(
                status: Response::HTTP_CONFLICT,
                headers: [Header::EXTERNAL => $url],
            );
        }

        return new RedirectResponse($url);
    }
}
